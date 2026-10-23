<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GlobalSearchService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $term, ?int $limit = null): Collection
    {
        $term = trim($term);
        $limit ??= (int) config('global-search.dropdown_limit', 8);

        if (mb_strlen($term) < (int) config('global-search.minimum_characters', 2)) {
            return collect();
        }

        return collect(config('global-search.sources', []))
            ->flatMap(fn (array $source, string $key) => $this->searchSource($key, $source, $term, $limit))
            ->sortBy([
                ['score', 'asc'],
                ['title', 'asc'],
            ])
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchSource(string $key, array $source, string $term, int $limit): Collection
    {
        $modelClass = $source['model'] ?? null;

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("Model sumber pencarian [{$key}] tidak valid.");
        }

        $fields = array_values($source['searchable'] ?? []);
        if ($fields === []) {
            return collect();
        }

        /** @var Model $model */
        $model = new $modelClass;
        $query = $model->newQuery();

        if ($relations = ($source['with'] ?? [])) {
            $query->with($relations);
        }

        $query->where(function (Builder $query) use ($fields, $term): void {
            foreach ($fields as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}($field, 'like', "%{$term}%");
            }
        });

        return $query->limit($limit)->get()->map(function (Model $record) use ($key, $source, $term): array {
            $title = $this->interpolate((string) ($source['title'] ?? '{id}'), $record);

            return [
                'source' => $key,
                'type' => (string) ($source['label'] ?? Str::headline($key)),
                'icon' => (string) ($source['icon'] ?? Str::upper(Str::substr($key, 0, 1))),
                'icon_class' => (string) ($source['icon_class'] ?? 'bg-slate-150 text-slate-500 dark:bg-navy-500 dark:text-navy-100'),
                'title' => $title,
                'subtitle' => $this->interpolate((string) ($source['subtitle'] ?? ''), $record),
                'url' => $this->makeUrl($source, $record),
                'score' => $this->score($title, $term),
            ];
        });
    }

    protected function makeUrl(array $source, Model $record): string
    {
        $parameters = collect($source['route_parameters'] ?? [])
            ->map(fn (string $template) => $this->interpolate($template, $record))
            ->all();

        $url = route($source['route'], $parameters);
        $query = collect($source['query'] ?? [])
            ->map(fn (string $template) => $this->interpolate($template, $record))
            ->filter(fn ($value) => $value !== '')
            ->all();

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    protected function interpolate(string $template, Model $record): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function (array $match) use ($record): string {
            return (string) data_get($record, $match[1], '');
        }, $template) ?? $template;
    }

    protected function score(string $title, string $term): int
    {
        $title = Str::lower($title);
        $term = Str::lower($term);

        return match (true) {
            $title === $term => 0,
            Str::startsWith($title, $term) => 1,
            default => 2,
        };
    }

    /** @return array<int, string> */
    public function recent(): array
    {
        return array_values(session('global_search.recent', []));
    }

    public function remember(string $term): void
    {
        $term = trim($term);
        if (mb_strlen($term) < (int) config('global-search.minimum_characters', 2)) {
            return;
        }

        $recent = collect($this->recent())
            ->reject(fn (string $item) => Str::lower($item) === Str::lower($term))
            ->prepend($term)
            ->take((int) config('global-search.recent_limit', 5))
            ->values()
            ->all();

        session(['global_search.recent' => $recent]);
    }
}
