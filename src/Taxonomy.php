<?php

namespace PostTypes;

class Taxonomy
{
    /**
     * The names passed to the Taxonomy
     * @var mixed
     */
    public $names;

    /**
     * The Taxonomy name
     * @var string
     */
    public $name;

    /**
     * The singular label for the Taxonomy
     * @var string
     */
    public $singular;

    /**
     * The plural label for the Taxonomy
     * @var string
     */
    public $plural;

    /**
     * The Taxonomy slug
     * @var name
     */
    public $slug;

    /**
     * Custom options for the Taxonomy
     * @var array
     */
    public $options;

    /**
     * Custom labels for the Taxonomy
     * @var array
     */
    public $labels;

    /**
     * PostTypes to register the Taxonomy to
     * @var array
     */
    public $posttypes = [];

    /**
     * The column manager for the Taxonomy
     * @var mixed
     */
    public $columns;

    /**
     * Create a Taxonomy
     * @param mixed $names The name(s) for the Taxonomy
     */
    public function __construct($names, $options = [], $labels = [])
    {
        $this->names($names);

        $this->options($options);

        $this->labels($labels);
    }

    /**
     * Set the names for the Taxonomy
     * @param  mixed $names The name(s) for the Taxonomy
     * @return $this
     */
    public function names($names)
    {
        if (is_string($names)) {
            $names = ['name' => $names];
        }

        $this->names = $names;

        // create names for the Taxonomy
        $this->createNames();

        return $this;
    }

    /**
     * Set options for the Taxonomy
     * @param  array  $options
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set the Taxonomy labels
     * @param  array  $labels
     * @return $this
     */
    public function labels(array $labels = [])
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * Assign a PostType to register the Taxonomy to
     * @param  string $posttype
     * @return $this
     */
    public function posttype($posttype)
    {
        $this->posttypes[] = $posttype;
    }

    /**
     * Register the Taxonomy to WordPress
     * @return void
     */
    public function register()
    {
        // register the taxonomy, set priority to 9
        // so taxonomies are registered before PostTypes
        add_action('init', [&$this, 'registerTaxonomy'], 9);

        // assign taxonomy to post type objects
        add_action('init', [&$this, 'registerTaxonomyToObjects']);

        if (isset($this->columns)) {
            // modify the columns for the Taxonomy
            add_filter("manage_edit-{$this->name}_columns", [&$this, 'modifyColumns']);

            // populate the columns for the Taxonomy
            add_filter("manage_{$this->name}_custom_column", [&$this, 'popualteColumns'], 10, 3);
        }
    }

    /**
     * Register the Taxonomy to WordPress
     * @return void
     */
    public function registerTaxonomy()
    {
        if (!taxonomy_exists($this->name)) {
            // create options for the Taxonomy
            $options = $this->createOptions();

            // register the Taxonomy with WordPress
            register_taxonomy($this->name, null, $options);
        }
    }

    public function registerTaxonomyToObjects()
    {
        // register Taxonomy to each of the PostTypes assigned
        if (!empty($this->posttypes)) {
            foreach ($this->posttypes as $posttype) {
                register_taxonomy_for_object_type($this->name, $posttype);
            }
        }
    }

    /**
     * Create names for the Taxonomy
     * @return void
     */
    public function createNames()
    {
        $required = [
            'name',
            'singular',
            'plural',
            'slug',
        ];

        foreach ($required as $key) {
            // if the name is set, assign it
            if (isset($this->names[$key])) {
                $this->$key = $this->names[$key];
                continue;
            }

            // if the key is not set and is singular or plural
            if (in_array($key, ['singular', 'plural'])) {
                // create a human friendly name
                $name = ucwords(strtolower(str_replace(['-', '_'], ' ', $this->names['name'])));
            }

            if ($key === 'slug') {
                // create a slug friendly name
                $name = strtolower(str_replace([' ', '_'], '-', $this->names['name']));
            }

            // if is plural or slug, append an 's'
            if (in_array($key, ['plural', 'slug'])) {
                $name .= 's';
            }

            // asign the name to the PostType property
            $this->$key = $name;
        }
    }

    /**
     * Create options for Taxonomy
     * @return array Options to pass to register_taxonomy
     */
    public function createOptions()
    {
        // default options
        $options = [
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => [
                'slug' => $this->slug,
            ],
        ];

        // replace defaults with the options passed
        $options = array_replace_recursive($options, $this->options);

        // create and set labels
        if (!isset($options['labels'])) {
            $options['labels'] = $this->createLabels();
        }

        return $options;
    }

    /**
     * Create labels for the Taxonomy
     * @return array
     */
    public function createLabels()
    {
        // default labels
        $labels = [
            'name' => $this->plural,
            'singular_name' => $this->singular,
            'menu_name' => $this->plural,
            'all_items' => "All {$this->plural}",
            'edit_item' => "Edit {$this->singular}",
            'view_item' => "View {$this->singular}",
            'update_item' => "Update {$this->singular}",
            'add_new_item' => "Add New {$this->singular}",
            'new_item_name' => "New {$this->singular} Name",
            'parent_item' => "Parent {$this->plural}",
            'parent_item_colon' => "Parent {$this->plural}:",
            'search_items' => "Search {$this->plural}",
            'popular_items' => "Popular {$this->plural}",
            'separate_items_with_commas' => "Seperate {$this->plural} with commas",
            'add_or_remove_items' => "Add or remove {$this->plural}",
            'choose_from_most_used' => "Choose from most used {$this->plural}",
            'not_found' => "No {$this->plural} found",
        ];

        return array_replace($labels, $this->labels);
    }

    /**
     * Get the Column Manager for the Taxonomy
     * @return Columns
     */
    public function columns()
    {
        if (!isset($this->columns)) {
            $this->columns = new Columns;
        }

        return $this->columns;
    }

    /**
     * Modify the columns for the Taxonomy
     * @param  array  $columns  The WordPress default columns
     * @return array
     */
    public function modifyColumns($columns)
    {
        $columns = $this->columns->modifyColumns($columns);

        return $columns;
    }

    /**
     * Populate custom columns for the Taxonomy
     * @param  string $content
     * @param  string $column
     * @param  int    $term_id
     */
    public function popualteColumns($content, $column, $term_id)
    {
        if (isset($this->columns->populate[$column])) {
            $content = call_user_func_array($this->columns()->populate[$column], [$content, $column, $term_id]);
        }

        return $content;
    }
}
