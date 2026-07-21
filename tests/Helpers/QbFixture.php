<?php
/**
 * Generic BaseModel fixture used to exercise the QueryBuilder in isolation.
 *
 * Deliberately feature-agnostic: it maps to a throwaway `debug_suite_qb_fixtures`
 * table (created/dropped inside the test) with a mix of string, integer and float
 * columns so every QueryBuilder capability can be tested without depending on any
 * shipped model.
 *
 * @package DebugSuite\Tests\Helpers
 */

namespace DebugSuite\Tests\Helpers;

use DebugSuite\Models\BaseModel;

/**
 * QueryBuilder test fixture model.
 */
class QbFixture extends BaseModel {

	/**
	 * Table name without prefix.
	 *
	 * @var string
	 */
	protected static string $table = 'qb_fixtures';

	/**
	 * Primary key column name.
	 *
	 * @var string
	 */
	protected static string $primary_key = 'id';

	/**
	 * Fillable columns for mass assignment.
	 *
	 * @var array
	 */
	protected static array $fillable = [
		'id',
		'name',
		'category',
		'value',
		'score',
		'label',
		'created_at',
		'updated_at',
	];

	/**
	 * Timestamps columns.
	 *
	 * @var array
	 */
	protected static array $timestamps = [ 'created_at', 'updated_at' ];

	/**
	 * Attribute casting definitions.
	 *
	 * @var array<string, string>
	 */
	protected static array $casts = [
		'id'    => 'integer',
		'value' => 'integer',
		'score' => 'float',
	];
}
