<?php

namespace App\Concerns;

use App\Models\TagModel;

trait HasTags
{
    protected bool $includeTags = false;
    protected array $tags       = [];

    public function __construct()
    {
        $this->beforeInsert[]  = 'tagsBeforeInsert';
        $this->afterInsert[]   = 'tagsAfterInsert';
        $this->beforeUpdate[]  = 'tagsBeforeUpdate';
        $this->afterUpdate[]   = 'tagsAfterUpdate';
        $this->afterFind[]     = 'tagsAfterFind';
        $this->allowedFields[] = 'tags';

        parent::__construct();
    }

    public function withTags(): static
    {
        $this->includeTags = true;

        return $this;
    }

    /**
     * Add tags manually.
     */
    public function addTags(array $tags): static
    {
        $this->tags = array_filter(array_unique(array_merge($this->tags, $tags)));

        $this->withTags();

        return $this;
    }

    /**
     * Remove tags manually.
     */
    public function removeTags(array $tags): static
    {
        $this->tags = array_filter(array_diff($this->tags, $tags));

        $this->withTags();

        return $this;
    }

    /**
     * Replace tags manually.
     */
    public function replaceTags(array $tags): static
    {
        $this->tags = array_filter(array_unique($tags));

        $this->withTags();

        return $this;
    }

    /**
     * Before insert event.
     */
    protected function tagsBeforeInsert(array $eventData): array
    {
        if (array_key_exists('tags', $eventData['data'])) {
            if (is_string($eventData['data']['tags'])) {
                $eventData['data']['tags'] = explode(',', $eventData['data']['tags']);
            }
            $this->addTags((array) $eventData['data']['tags']);
            unset($eventData['data']['tags']);
        }

        return $eventData;
    }

    /**
     * After insert event.
     */
    protected function tagsAfterInsert(array $eventData): void
    {
        if ($this->includeTags && $eventData['result']) {
            model(TagModel::class)->createTags($this->tags, $eventData['id']);
            $this->tags = [];
        }
    }

    /**
     * Before update event.
     */
    protected function tagsBeforeUpdate(array $eventData): array
    {
        if (array_key_exists('tags', $eventData['data'])) {
            if (is_string($eventData['data']['tags'])) {
                $eventData['data']['tags'] = explode(',', $eventData['data']['tags']);
            }
            $this->addTags((array) $eventData['data']['tags']);
            unset($eventData['data']['tags']);
        }

        return $eventData;
    }

    /**
     * After update event.
     */
    protected function tagsAfterUpdate(array $eventData): void
    {
        if ($this->includeTags && $eventData['result']) {
            foreach ($eventData['id'] as $id) {
                model(TagModel::class)->updateTags($this->tags, $id);
            }
            $this->tags = [];
        }
    }

    /**
     * After find event.
     */
    protected function tagsAfterFind(array $eventData): array
    {
        if (! $this->includeTags || empty($eventData['data'])) {
            return $eventData;
        }

        $tagModel = model(TagModel::class);

        if ($eventData['singleton']) {
            if ($this->returnType === 'array') {
                $eventData['data']['tags'] = $tagModel->getByThreadId($eventData['data'][$this->primaryKey]);
            } else {
                $eventData['data']->tags = $tagModel->getByThreadId($eventData['data']->{$this->primaryKey});
            }
        } else {
            $keys = array_map('intval', array_column($eventData['data'], $this->primaryKey));
            $tags = $tagModel->getByThreadIds($keys);

            foreach ($eventData['data'] as &$data) {
                if ($this->returnType === 'array') {
                    $data['tags'] = $tags[$data[$this->primaryKey]] ?? [];
                } else {
                    $data->tags = $tags[$data->{$this->primaryKey}] ?? [];
                }
            }
        }

        return $eventData;
    }
}