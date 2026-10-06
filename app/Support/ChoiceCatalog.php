<?php

namespace App\Support;

use App\Models\ChoiceCategory;
use App\Models\ChoiceOption;
use Illuminate\Support\Collection;

/**
 * Loads the dropdown masters once per request.
 *
 * Survey responses store a `choice_options` id per answer, so rendering a list of
 * responses would otherwise need one eager load per answer column. The whole catalog
 * is small enough (well under a hundred rows) to hold in memory instead.
 */
class ChoiceCatalog
{
    /** @var Collection<string, Collection<int, ChoiceOption>>|null */
    private ?Collection $byCategory = null;

    /** @var Collection<int, ChoiceOption>|null */
    private ?Collection $byId = null;

    /**
     * Active options for a category key, ready to render as a dropdown.
     *
     * @return Collection<int, ChoiceOption>
     */
    public function options(string $categoryKey): Collection
    {
        return $this->grouped()->get($categoryKey, new Collection)
            ->filter(fn (ChoiceOption $option): bool => $option->is_active)
            ->values();
    }

    /**
     * Active options plus those currently selected, so an answer that points at a
     * deactivated option keeps showing it instead of silently resetting to blank.
     * Options whose value is in $except are left out unless selected, for a question that
     * shares a list but does not offer all of it.
     *
     * @param  int|list<int>|null  $selected  one id, or several for a multi-select
     * @param  list<string>  $except  option values this question does not offer
     * @return Collection<int, ChoiceOption>
     */
    public function optionsIncluding(string $categoryKey, int|array|null $selected, array $except = []): Collection
    {
        $selectedIds = array_map(intval(...), (array) $selected);

        return $this->grouped()->get($categoryKey, new Collection)
            ->filter(fn (ChoiceOption $option): bool => in_array($option->id, $selectedIds, true)
                || ($option->is_active && ! in_array($option->value, $except, true)))
            ->values();
    }

    public function label(?int $optionId): ?string
    {
        if ($optionId === null) {
            return null;
        }

        return $this->keyedById()->get($optionId)?->label;
    }

    public function value(?int $optionId): ?string
    {
        if ($optionId === null) {
            return null;
        }

        return $this->keyedById()->get($optionId)?->value;
    }

    /**
     * Resolves the seeded `value` of an option to its id, for aggregations that must keep
     * working after an administrator has reworded or deactivated the option's label.
     */
    public function optionIdByValue(string $categoryKey, string $value): ?int
    {
        return $this->grouped()->get($categoryKey, new Collection)
            ->firstWhere('value', $value)?->id;
    }

    /**
     * Ids of every option in a category, used to validate submitted answers, less those whose
     * value is in $except.
     *
     * @param  list<string>  $except  option values the question does not offer
     * @return list<int>
     */
    public function optionIds(string $categoryKey, array $except = []): array
    {
        return $this->grouped()->get($categoryKey, new Collection)
            ->reject(fn (ChoiceOption $option): bool => in_array($option->value, $except, true))
            ->pluck('id')
            ->values()
            ->all();
    }

    /** @return Collection<string, Collection<int, ChoiceOption>> */
    private function grouped(): Collection
    {
        return $this->byCategory ??= ChoiceCategory::query()
            ->with('options')
            ->get()
            ->keyBy('key')
            ->map(fn (ChoiceCategory $category): Collection => $category->options);
    }

    /** @return Collection<int, ChoiceOption> */
    private function keyedById(): Collection
    {
        return $this->byId ??= $this->grouped()->flatten(1)->keyBy('id');
    }
}
