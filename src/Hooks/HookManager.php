<?php

declare(strict_types=1);

namespace Richness\RichAddons\Hooks;

/**
 * WordPress-inspired Hook Manager — supports both Actions (fire-and-forget
 * side-effects) and Filters (value transformation through a pipeline).
 *
 * Usage from host app or add-ons:
 *   rich_action('rich.order.after_create', $order);
 *   $price = rich_filter('rich.cart.line_price', $price, $lineItem);
 */
final class HookManager
{
    /** @var array<string, list<array{callback: callable, priority: int}>> */
    private array $actions = [];

    /** @var array<string, list<array{callback: callable, priority: int}>> */
    private array $filters = [];

    // ──────────────────────────────────────────────────────
    //  Actions — fire-and-forget, no return value
    // ──────────────────────────────────────────────────────

    public function addAction(string $tag, callable $callback, int $priority = 10): void
    {
        $this->actions[$tag][] = ['callback' => $callback, 'priority' => $priority];
    }

    public function doAction(string $tag, mixed ...$args): void
    {
        if (! isset($this->actions[$tag])) {
            return;
        }

        $sorted = $this->sortByPriority($this->actions[$tag]);

        foreach ($sorted as $entry) {
            ($entry['callback'])(...$args);
        }
    }

    public function hasAction(string $tag): bool
    {
        return ! empty($this->actions[$tag]);
    }

    // ──────────────────────────────────────────────────────
    //  Filters — pass a value through a pipeline, return it
    // ──────────────────────────────────────────────────────

    public function addFilter(string $tag, callable $callback, int $priority = 10): void
    {
        $this->filters[$tag][] = ['callback' => $callback, 'priority' => $priority];
    }

    public function applyFilters(string $tag, mixed $value, mixed ...$args): mixed
    {
        if (! isset($this->filters[$tag])) {
            return $value;
        }

        $sorted = $this->sortByPriority($this->filters[$tag]);

        foreach ($sorted as $entry) {
            $value = ($entry['callback'])($value, ...$args);
        }

        return $value;
    }

    public function hasFilter(string $tag): bool
    {
        return ! empty($this->filters[$tag]);
    }

    // ──────────────────────────────────────────────────────
    //  Debugging & Introspection
    // ──────────────────────────────────────────────────────

    /** @return list<string> */
    public function registeredActions(): array
    {
        return array_keys($this->actions);
    }

    /** @return list<string> */
    public function registeredFilters(): array
    {
        return array_keys($this->filters);
    }

    public function listenerCount(string $tag): int
    {
        return count($this->actions[$tag] ?? []) + count($this->filters[$tag] ?? []);
    }

    /**
     * Remove all registered listeners. Useful for testing.
     */
    public function flush(): void
    {
        $this->actions = [];
        $this->filters = [];
    }

    // ──────────────────────────────────────────────────────
    //  Internal
    // ──────────────────────────────────────────────────────

    /**
     * @param  list<array{callback: callable, priority: int}>  $entries
     * @return list<array{callback: callable, priority: int}>
     */
    private function sortByPriority(array $entries): array
    {
        usort($entries, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $entries;
    }
}
