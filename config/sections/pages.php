<?php

use Kirby\Cms\Blueprint;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Escape;
use Kirby\Toolkit\I18n;

return [
    'mixins' => [
        'empty',
        'headline',
        'help',
        'layout',
        'min',
        'max',
        'pagination',
        'parent'
    ],
    'props' => [
        /**
         * Optional array of templates that should only be allowed to add
         * or `false` to completely disable page creation
         */
        'create' => function ($create = null) {
            return $create;
        },
        /**
         * Enables/disables reverse sorting
         */
        'flip' => function (bool $flip = false) {
            return $flip;
        },
        /**
         * Image options to control the source and look of page previews
         */
        'image' => function ($image = null) {
            return $image ?? [];
        },
        /**
         * Optional info text setup. Info text is shown on the right (lists) or below (cards) the page title.
         */
        'info' => function ($info = null) {
            return I18n::translate($info, $info);
        },
        /**
         * The size option controls the size of cards. By default cards are auto-sized and the cards grid will always fill the full width. With a size you can disable auto-sizing. Available sizes: `tiny`, `small`, `medium`, `large`, `huge`
         */
        'size' => function (string $size = 'auto') {
            return $size;
        },
        /**
         * Enables/disables manual sorting
         */
        'sortable' => function (bool $sortable = true) {
            return $sortable;
        },
        /**
         * Overwrites manual sorting and sorts by the given field and sorting direction (i.e. `date desc`)
         */
        'sortBy' => function (string $sortBy = null) {
            return $sortBy;
        },
        /**
         * Filters pages by their status. Available status settings: `draft`, `unlisted`, `listed`, `published`, `all`.
         */
        'status' => function (string $status = '') {
            if ($status === 'drafts') {
                $status = 'draft';
            }

            if (in_array($status, ['all', 'draft', 'published', 'listed', 'unlisted']) === false) {
                $status = 'all';
            }

            return $status;
        },
        /**
         * Filters the list by templates and sets template options when adding new pages to the section.
         */
        'templates' => function ($templates = null) {
            return A::wrap($templates ?? $this->template);
        },
        /**
         * Setup for the main text in the list or cards. By default this will display the page title.
         */
        'text' => function ($text = '{{ page.title }}') {
            return I18n::translate($text, $text);
        }
    ],
    'computed' => [
        'parent' => function () {
            return $this->parentModel();
        },
        'add' => function () {
            if ($this->create === false) {
                return false;
            }

            if (in_array($this->status, ['draft', 'all']) === false) {
                return false;
            }

            return true;
        },
        'link' => function () {
            $modelLink  = $this->model->panel()->url(true);
            $parentLink = $this->parent->panel()->url(true);

            if ($modelLink !== $parentLink) {
                return $parentLink;
            }
        },
        'sortable' => function () {
            if (in_array($this->status, ['listed', 'published', 'all']) === false) {
                return false;
            }

            if ($this->sortable === false) {
                return false;
            }

            if ($this->sortBy !== null) {
                return false;
            }

            if ($this->flip === true) {
                return false;
            }

            return true;
        }
    ],
    'methods' => [
        'blueprints' => function () {
            $blueprints = [];
            $templates  = empty($this->create) === false ? A::wrap($this->create) : $this->templates;

            if (empty($templates) === true) {
                $templates = $this->kirby()->blueprints();
            }

            // convert every template to a usable option array
            // for the template select box
            foreach ($templates as $template) {
                try {
                    $props = Blueprint::load('pages/' . $template);

                    $blueprints[] = [
                        'name'  => basename($props['name']),
                        'title' => $props['title'],
                    ];
                } catch (Throwable $e) {
                    $blueprints[] = [
                        'name'  => basename($template),
                        'title' => ucfirst($template),
                    ];
                }
            }

            return $blueprints;
        },

    ],
    'api' => function () {
        $section = $this;

        return [
            [
                'pattern' => '/',
                'action'  => function () use ($section) {

                    switch ($section->status) {
                        case 'draft':
                            $pages = $section->parent->drafts();
                            break;
                        case 'listed':
                            $pages = $section->parent->children()->listed();
                            break;
                        case 'published':
                            $pages = $section->parent->children();
                            break;
                        case 'unlisted':
                            $pages = $section->parent->children()->unlisted();
                            break;
                        default:
                            $pages = $section->parent->childrenAndDrafts();
                    }

                    // loop for the best performance
                    foreach ($pages->data as $id => $page) {

                        // remove all protected pages
                        if ($page->isReadable() === false) {
                            unset($pages->data[$id]);
                            continue;
                        }

                        // filter by all set templates
                        if ($section->templates && in_array($page->intendedTemplate()->name(), $section->templates) === false) {
                            unset($pages->data[$id]);
                            continue;
                        }
                    }

                    // sort
                    if ($section->sortBy) {
                        $pages = $pages->sort(...$pages::sortArgs($section->sortBy));
                    }

                    // flip
                    if ($section->flip === true) {
                        $pages = $pages->flip();
                    }

                    // pagination
                    $pages = $pages->paginate([
                        'page'   => $section->page,
                        'limit'  => $section->limit,
                        'method' => 'none' // the page is manually provided
                    ]);

                    $pagination = $pages->pagination();
                    $items      = [];

                    foreach ($pages as $item) {
                        $panel       = $item->panel();
                        $permissions = $item->permissions();

                        // escape the default text
                        // TODO: no longer needed in 3.6
                        $text = $item->toString($section->text);

                        if ($section->text === '{{ page.title }}') {
                            $text = Escape::html($text);
                        }

                        $items[] = [
                            'id'          => $item->id(),
                            'dragText'    => $panel->dragText(),
                            'text'        => $text,
                            'info'        => $item->toString($section->info ?? false),
                            'parent'      => $item->parentId(),
                            'image'       => $panel->image($section->image, $section->layout),
                            'link'        => $panel->url(true),
                            'template'    => $item->intendedTemplate()->name(),
                            'status'      => $item->status(),
                            'permissions' => [
                                'sort'         => $permissions->can('sort'),
                                'changeSlug'   => $permissions->can('changeSlug'),
                                'changeStatus' => $permissions->can('changeStatus'),
                                'changeTitle'  => $permissions->can('changeTitle'),
                            ]
                        ];
                    }

                    return [
                        'items'      => $items,
                        'pagination' => [
                            'page'  => $pagination->page(),
                            'total' => $pagination->total()
                        ]
                    ];
                }
            ]
        ];
    },
    'toArray' => function () {
        return [
            'add'      => $this->add,
            'empty'    => $this->empty,
            'headline' => $this->headline,
            'help'     => $this->help,
            'layout'   => $this->layout,
            'link'     => $this->link,
            'max'      => $this->max,
            'min'      => $this->min,
            'size'     => $this->size,
            'sortable' => $this->sortable
        ];
    }
];
