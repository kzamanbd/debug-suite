<?php

namespace DebugSuite\Pages;

/**
 * Interface Pageable.
 *
 * @package DebugSuite\Pages
 *
 * @since 1.0.0
 */
interface Pageable {

    /**
     * Get the ID of the page.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_id(): string;

    /**
     * Get the menu arguments.
     *
     * @since 1.0.0
     *
     * @param  string  $capability Menu capability.
     * @param  string  $position Menu position.
     *
     * @return array<string, string|int> An array of associative arrays with keys 'route', 'page_title', 'menu_title', 'capability', 'position'.
     */
    public function menu( string $capability, string $position ): array;

    /**
     * Get the settings values.
     *
     * @since 1.0.0
     *
     * @return array<string,mixed> An array of settings values.
     */
    public function settings(): array;

    /**
     * Get the scripts.
     *
     * @since 1.0.0
     *
     * @return array<string> An array of script handles.
     */
    public function scripts(): array;

    /**
     * Get the styles.
     *
     * @since 1.0.0
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array;

    /**
     * Register the page scripts and styles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void;
}
