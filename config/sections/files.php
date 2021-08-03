<?php

use Kirby\Cms\File;
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
        'parent',
    ],
    'props' => [
        /**
         * Enables/disables reverse sorting
         */
        'flip' => function (bool $flip = false) {
            return $flip;
        },
        /**
         * Image options to control the source and look of file previews
         */
        'image' => function ($image = null) {
            return $image ?? [];
        },
        /**
         * Optional info text setup. Info text is shown on the right (lists) or below (cards) the filename.
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
         * Overwrites manual sorting and sorts by the given field and sorting direction (i.e. `filename desc`)
         */
        'sortBy' => function (string $sortBy = null) {
            return $sortBy;
        },
        /**
         * Filters all files by template and also sets the template, which will be used for all uploads
         */
        'template' => function (string $template = null) {
            return $template;
        },
        /**
         * Setup for the main text in the list or cards. By default this will display the filename.
         */
        'text' => function ($text = '{{ file.filename }}') {
            return I18n::translate($text, $text);
        }
    ],
    'computed' => [
        'accept' => function () {
            if ($this->template) {
                $file = new File([
                    'filename' => 'tmp',
                    'parent'   => $this->model(),
                    'template' => $this->template
                ]);

                return $file->blueprint()->acceptMime();
            }

            return null;
        },
        'parent' => function () {
            return $this->parentModel();
        },
        'link' => function () {
            $modelLink  = $this->model->panel()->url(true);
            $parentLink = $this->parent->panel()->url(true);

            if ($modelLink !== $parentLink) {
                return $parentLink;
            }
        },
        'sortable' => function () {
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
        },
        'upload' => function () {
            $template = $this->template === 'default' ? null : $this->template;

            return [
                'accept'     => $this->accept,
                'multiple'   => true,
                'max'        => $this->max,
                'api'        => $this->parent->apiUrl(true) . '/files',
                'attributes' => array_filter([
                    'template' => $template
                ])
            ];
        }
    ],
    'api' => function() {

        $section = $this;

        return [
            [
                'pattern' => '/',
                'action'  => function () use ($section) {

                    $files = $section->parent->files()->template($section->template);

                    // filter out all protected files
                    $files = $files->filter('isReadable', true);

                    if ($section->sortBy) {
                        $files = $files->sort(...$files::sortArgs($section->sortBy));
                    } else {
                        $files = $files->sorted();
                    }

                    // flip
                    if ($section->flip === true) {
                        $files = $files->flip();
                    }

                    // apply the default pagination
                    $files = $files->paginate([
                        'page'   => $section->page,
                        'limit'  => $section->limit,
                        'method' => 'none' // the page is manually provided
                    ]);

                    $pagination = $files->pagination();
                    $items      = [];

                    // the drag text needs to be absolute when the files come from
                    // a different parent model
                    $dragTextAbsolute = $section->model->is($section->parent) === false;

                    foreach ($files as $file) {
                        $panel = $file->panel();

                        // escape the default text
                        // TODO: no longer needed in 3.6
                        $text = $file->toString($section->text);
                        if ($section->text === '{{ file.filename }}') {
                            $text = Escape::html($text);
                        }

                        $items[] = [
                            'dragText'  => $panel->dragText('auto', $dragTextAbsolute),
                            'extension' => $file->extension(),
                            'filename'  => $file->filename(),
                            'id'        => $file->id(),
                            'image'     => $panel->image($section->image, $section->layout),
                            'info'      => $file->toString($section->info ?? false),
                            'link'      => $panel->url(true),
                            'mime'      => $file->mime(),
                            'parent'    => $file->parent()->panel()->path(),
                            'template'  => $file->template(),
                            'text'      => $text,
                            'url'       => $file->url(),
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
            'accept'   => $this->accept,
            'apiUrl'   => $this->parent->apiUrl(true),
            'empty'    => $this->empty,
            'headline' => $this->headline,
            'help'     => $this->help,
            'layout'   => $this->layout,
            'link'     => $this->link,
            'max'      => $this->max,
            'min'      => $this->min,
            'size'     => $this->size,
            'sortable' => $this->sortable,
            'upload'   => $this->upload
        ];
    }
];
