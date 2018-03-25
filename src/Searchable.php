<?php

namespace ScoutElastic;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable as ScoutSearchable;
use ScoutElastic\Builders\FilterBuilder;
use ScoutElastic\Builders\SearchBuilder;
use \Exception;

trait Searchable {
    use ScoutSearchable {
        ScoutSearchable::bootSearchable as bootScoutSearchable;
    }

    private static $isSearchableTraitBooted = false;

    public static function bootSearchable()
    {
        if (self::$isSearchableTraitBooted) {
            return;
        }

        self::bootScoutSearchable();

        self::$isSearchableTraitBooted = true;
    }

    /**
     * @return IndexConfigurator
     * @throws Exception If an index configurator is not specified
     */
    public function getIndexConfigurator()
    {
        static $indexConfigurator;

        if (!$indexConfigurator) {
            if (!isset($this->indexConfigurator) || empty($this->indexConfigurator)) {
                throw new Exception(sprintf('An index configurator for the %s model is not specified.', __CLASS__));
            }

            $indexConfiguratorClass = $this->indexConfigurator;
            $indexConfigurator = new $indexConfiguratorClass;
        }

        return $indexConfigurator;
    }

    public function getMapping()
    {
        $mapping = $this->mapping ?? [];

        if ($this->usesSoftDelete() && config('scout.soft_delete', false)) {
            array_set($mapping, 'properties.__soft_deleted', ['type' => 'integer']);
        }

        return $mapping;
    }

    public function getSearchRules()
    {
        return isset($this->searchRules) && count($this->searchRules) > 0 ? $this->searchRules : [SearchRule::class];
    }

    public static function search($query, $callback = null)
    {
        $softDelete = config('scout.soft_delete', false);

        if ($query == '*') {
            return new FilterBuilder(new static, $callback, $softDelete);
        } else {
            return new SearchBuilder(new static, $query, $callback, $softDelete);
        }
    }

    public static function searchRaw($query)
    {
        $model = new static();

        return $model->searchableUsing()
            ->searchRaw($model, $query);
    }

    /**
     * @return bool
     */
    public function usesSoftDelete()
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this));
    }
}
