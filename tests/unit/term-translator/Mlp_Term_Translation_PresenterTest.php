<?php # -*- coding: utf-8 -*-

use WP_Mock\Tools\TestCase;

/**
 * Test case for the Mlp_Term_Translation_Presenter class.
 */
class Mlp_Term_Translation_PresenterTest extends TestCase {

	/**
	 * @covers Mlp_Term_Translation_Presenter::get_key_base
	 *
	 * @return void
	 */
	public function test_get_key_base() {

		/** @var Mlp_Content_Relations_Interface $content_relations */
		$content_relations = Mockery::mock( 'Mlp_Content_Relations_Interface' );

		/** @var Inpsyde_Nonce_Validator_Interface $nonce */
		$nonce = Mockery::mock( 'Inpsyde_Nonce_Validator_Interface' );

		$key_base = 'key_base';

		WP_Mock::wpFunction(
			'get_current_blog_id',
			array(
				'times'  => 1,
				'return' => 42,
			)
		);

		WP_Mock::wpFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => (object) array( 'taxonomy' => 'taxonomy' ),
			)
		);

		$testee = new Mlp_Term_Translation_Presenter(
			$content_relations,
			$nonce,
			$key_base
		);

		$this->assertSame( 'key_base[42]', $testee->get_key_base( 42 ) );
	}

	/**
	 * @covers       Mlp_Term_Translation_Presenter::get_terms_for_site
	 * @dataProvider provide_get_terms_for_site_data
	 *
	 * @param array $expected
	 * @param bool  $current_user_can
	 * @param int   $times_get_terms
	 * @param array $terms
	 * @param bool  $is_taxonomy_hierarchical
	 * @param array $ancestors
	 *
	 * @return void
	 */
	public function test_get_terms_for_site(
		array $expected,
		$current_user_can,
		$times_get_terms,
		array $terms,
		$is_taxonomy_hierarchical,
		array $ancestors
	) {

		/** @var Mlp_Content_Relations_Interface $content_relations */
		$content_relations = Mockery::mock( 'Mlp_Content_Relations_Interface' );

		/** @var Inpsyde_Nonce_Validator_Interface $nonce */
		$nonce = Mockery::mock( 'Inpsyde_Nonce_Validator_Interface' );

		$key_base = 'key_base';

		WP_Mock::wpFunction(
			'get_current_blog_id',
			array(
				'times'  => 1,
				'return' => 42,
			)
		);

		$taxonomy = 'taxonomy';

		WP_Mock::wpFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => (object) array( 'taxonomy' => $taxonomy ),
			)
		);

		$testee = new Mlp_Term_Translation_Presenter(
			$content_relations,
			$nonce,
			$key_base
		);

		WP_mock::wpFunction(
			'switch_to_blog',
			array(
				'times' => 1,
				'args'  => array(
					Mockery::type( 'int' ),
				),
			)
		);

		WP_Mock::wpFunction(
			'get_taxonomy',
			array(
				'times'  => 1,
				'args'   => array(
					$taxonomy,
				),
				'return' => (object) array( 'cap' => (object) array( 'edit_terms' => 'edit_terms' ) ),
			)
		);

		WP_Mock::wpFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'args'   => array(
					Mockery::type( 'string' ),
				),
				'return' => $current_user_can,
			)
		);

		WP_Mock::wpFunction(
			'get_terms',
			array(
				'times'  => $times_get_terms,
				'args'   => array(
					$taxonomy,
					array( 'hide_empty' => FALSE ),
				),
				'return' => $terms,
			)
		);

		$times_in_foreach = count( $terms );

		WP_Mock::wpFunction(
			'is_taxonomy_hierarchical',
			array(
				'times'  => $times_in_foreach,
				'args'   => array(
					$taxonomy,
				),
				'return' => $is_taxonomy_hierarchical,
			)
		);

		WP_Mock::wpFunction(
			'get_ancestors',
			array(
				'times'  => $is_taxonomy_hierarchical ? $times_in_foreach : 0,
				'args'   => array(
					Mockery::type( 'int' ),
					$taxonomy,
				),
				'return' => $ancestors,
			)
		);

		$ancestor_terms = $ancestors;
		for ( $i = 1; $i < $times_in_foreach; $i++ ) {
			$ancestor_terms = array_merge( $ancestor_terms, $ancestors );
		}

		WP_Mock::wpFunction(
			'get_term',
			array(
				'times'           => $times_in_foreach * count( $ancestors ),
				'args'            => array(
					Mockery::type( 'object' ),
					$taxonomy,
				),
				'return_in_order' => $ancestor_terms,
			)
		);

		WP_mock::wpPassthruFunction(
			'esc_html',
			array(
				'times' => $times_in_foreach,
				'args'  => array(
					Mockery::type( 'string' ),
				),
			)
		);

		WP_mock::wpFunction(
			'restore_current_blog',
			array(
				'times' => 1,
			)
		);

		$this->assertSame( $expected, $testee->get_terms_for_site( 42 ) );
	}

	/**
	 * Provider for the test_get_terms_for_site() method.
	 *
	 * @return array
	 */
	public function provide_get_terms_for_site_data() {

		$term = (object) array(
			'term_id'          => 0, // 0 because that way the array appears non-associative
			'name'             => 'term',
			'term_taxonomy_id' => 0,
		);

		$terms = array(
			(object) array(
				'term_id'          => 0, // 0 because that way the array appears non-associative
				'name'             => 'term_0',
				'term_taxonomy_id' => 0,
			),
			(object) array(
				'term_id'          => 1,
				'name'             => 'term_1',
				'term_taxonomy_id' => 1,
			),
			(object) array(
				'term_id'          => 2,
				'name'             => 'term_2',
				'term_taxonomy_id' => 2,
			),
		);

		$ancestors = array(
			(object) array(
				'name' => 'mommy',
			),
			(object) array(
				'name' => 'granny',
			),
		);

		$expected_single_term_no_ancestors = array( 'term' );

		$expected_multiple_terms_no_ancestors = array(
			'term_0',
			'term_1',
			'term_2',
		);

		$expected_single_term_with_ancestors = array( 'granny/mommy/term' );

		$expected_multiple_terms_with_ancestors = array(
			'granny/mommy/term_0',
			'granny/mommy/term_1',
			'granny/mommy/term_2',
		);

		return array(
			'user_cannot_edit_terms'                  => array(
				'expected'                 => array(),
				'current_user_can'         => FALSE,
				'times_get_terms'          => 0,
				'terms'                    => array(),
				'is_taxonomy_hierarchical' => TRUE,
				'ancestors'                => array(),
			),
			'no_terms'                                => array(
				'expected'                 => array(),
				'current_user_can'         => TRUE,
				'times_get_terms'          => 1,
				'terms'                    => array(),
				'is_taxonomy_hierarchical' => TRUE,
				'ancestors'                => array(),
			),
			'single_term_non_hierarchical_taxonomy'   => array(
				'expected'                 => $expected_single_term_no_ancestors,
				'current_user_can'         => TRUE,
				'times_get_terms'          => 1,
				'terms'                    => array( $term ),
				'is_taxonomy_hierarchical' => FALSE,
				'ancestors'                => array(),
			),
			'muliple_terms_non_hierarchical_taxonomy' => array(
				'expected'                 => $expected_multiple_terms_no_ancestors,
				'current_user_can'         => TRUE,
				'times_get_terms'          => 1,
				'terms'                    => $terms,
				'is_taxonomy_hierarchical' => FALSE,
				'ancestors'                => array(),
			),
			'single_term_no_ancestors'                => array(
				'expected'                 => $expected_single_term_no_ancestors,
				'current_user_can'         => TRUE,
				'times_get_terms'          => 1,
				'terms'                    => array( $term ),
				'is_taxonomy_hierarchical' => TRUE,
				'ancestors'                => array(),
			),
			'muliple_terms_no_ancestors'              => array(
				'expected'                 => $expected_multiple_terms_no_ancestors,
				'current_user_can'         => TRUE,
				'times_get_terms'          => 1,
				'terms'                    => $terms,
				'is_taxonomy_hierarchical' => TRUE,
				'ancestors'                => array(),
			),
			'single_term_with_ancestors'              => array(
				'expected'                 => $expected_single_term_with_ancestors,
				'current_user_can'         => TRUE,
				'times_get_terms'          => 1,
				'terms'                    => array( $term ),
				'is_taxonomy_hierarchical' => TRUE,
				'ancestors'                => $ancestors,
			),
			'muliple_terms_with_ancestors'            => array(
				'expected'                 => $expected_multiple_terms_with_ancestors,
				'current_user_can'         => TRUE,
				'times_get_terms'          => 1,
				'terms'                    => $terms,
				'is_taxonomy_hierarchical' => TRUE,
				'ancestors'                => $ancestors,
			),
		);
	}

	/**
	 * @covers Mlp_Term_Translation_Presenter::get_taxonomy
	 *
	 * @return void
	 */
	public function test_get_taxonomy() {

		/** @var Mlp_Content_Relations_Interface $content_relations */
		$content_relations = Mockery::mock( 'Mlp_Content_Relations_Interface' );

		/** @var Inpsyde_Nonce_Validator_Interface $nonce */
		$nonce = Mockery::mock( 'Inpsyde_Nonce_Validator_Interface' );

		$key_base = 'key_base';

		WP_Mock::wpFunction(
			'get_current_blog_id',
			array(
				'times'  => 1,
				'return' => 42,
			)
		);

		$taxonomy = 'taxonomy';

		WP_Mock::wpFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => (object) array( 'taxonomy' => $taxonomy ),
			)
		);

		$testee = new Mlp_Term_Translation_Presenter(
			$content_relations,
			$nonce,
			$key_base
		);

		$this->assertSame( $taxonomy, $testee->get_taxonomy() );
	}

	/**
	 * @covers       Mlp_Term_Translation_Presenter::get_site_languages
	 * @dataProvider provide_get_site_languages_data
	 *
	 * @param array $expected
	 * @param array $languages
	 * @param int   $current_blog_id
	 *
	 * @return void
	 */
	public function test_get_site_languages( array $expected, array $languages, $current_blog_id ) {

		/** @var Mlp_Content_Relations_Interface $content_relations */
		$content_relations = Mockery::mock( 'Mlp_Content_Relations_Interface' );

		/** @var Inpsyde_Nonce_Validator_Interface $nonce */
		$nonce = Mockery::mock( 'Inpsyde_Nonce_Validator_Interface' );

		$key_base = 'key_base';

		WP_Mock::wpFunction(
			'get_current_blog_id',
			array(
				'times'  => 2,
				'return' => $current_blog_id,
			)
		);

		WP_Mock::wpFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => (object) array( 'taxonomy' => 'taxonomy' ),
			)
		);

		$testee = new Mlp_Term_Translation_Presenter(
			$content_relations,
			$nonce,
			$key_base
		);

		WP_Mock::wpFunction(
			'mlp_get_available_languages_titles',
			array(
				'times'  => 1,
				'return' => $languages,
			)
		);

		$this->assertSame( $expected, $testee->get_site_languages() );
	}

	/**
	 * Provider for the test_get_site_languages() method.
	 *
	 * @return array
	 */
	public function provide_get_site_languages_data() {

		$all_languages = array(
			1 => 'A',
			2 => 'B',
			3 => 'C',
		);

		$some_languages = array(
			2 => 'B',
			3 => 'C',
		);

		return array(
			'all_languages'  => array(
				'expected'        => $all_languages,
				'languages'       => $all_languages,
				'current_blog_id' => 42,
			),
			'some_languages' => array(
				'expected'        => $some_languages,
				'languages'       => $all_languages,
				'current_blog_id' => 1,
			),
		);
	}

	/**
	 * @covers Mlp_Term_Translation_Presenter::get_nonce_field
	 *
	 * @return void
	 */
	public function test_get_nonce_field() {

		/** @var Mlp_Content_Relations_Interface $content_relations */
		$content_relations = Mockery::mock( 'Mlp_Content_Relations_Interface' );

		/** @var Inpsyde_Nonce_Validator_Interface $nonce */
		$nonce = Mockery::mock( 'Inpsyde_Nonce_Validator_Interface' );
		$nonce->shouldReceive( 'get_action' )
			->once()
			->andReturn( $nonce_action = 'nonce_action' );

		$nonce->shouldReceive( 'get_name' )
			->once()
			->andReturn( $nonce_name = 'nonce_name' );

		$key_base = 'key_base';

		WP_Mock::wpFunction(
			'get_current_blog_id',
			array(
				'times'  => 1,
				'return' => 42,
			)
		);

		WP_Mock::wpFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => (object) array( 'taxonomy' => 'taxonomy' ),
			)
		);

		$testee = new Mlp_Term_Translation_Presenter(
			$content_relations,
			$nonce,
			$key_base
		);

		WP_Mock::wpPassthruFunction(
			'wp_nonce_field',
			array(
				'times' => 1,
				'args'  => array(
					$nonce_action,
					$nonce_name,
					TRUE,
					FALSE,
				),
			)
		);

		$this->assertSame( $nonce_action, $testee->get_nonce_field() );
	}

	/**
	 * @covers Mlp_Term_Translation_Presenter::get_group_title
	 *
	 * @return void
	 */
	public function test_get_group_title() {

		/** @var Mlp_Content_Relations_Interface $content_relations */
		$content_relations = Mockery::mock( 'Mlp_Content_Relations_Interface' );

		/** @var Inpsyde_Nonce_Validator_Interface $nonce */
		$nonce = Mockery::mock( 'Inpsyde_Nonce_Validator_Interface' );

		$key_base = 'key_base';

		WP_Mock::wpFunction(
			'get_current_blog_id',
			array(
				'times'  => 1,
				'return' => 42,
			)
		);

		WP_Mock::wpFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => (object) array( 'taxonomy' => 'taxonomy' ),
			)
		);

		$testee = new Mlp_Term_Translation_Presenter(
			$content_relations,
			$nonce,
			$key_base
		);

		WP_Mock::wpPassthruFunction(
			'esc_html__',
			array(
				'times' => 1,
				'args'  => array(
					'Translations',
					'multilingualpress',
				),
			)
		);

		$this->assertSame( 'Translations', $testee->get_group_title() );
	}

	/**
	 * @covers       Mlp_Term_Translation_Presenter::get_current_term
	 * @dataProvider provide_get_current_term_data
	 *
	 * @param int    $expected
	 * @param int    $site_id
	 * @param int    $term_id
	 * @param object $term
	 * @param array  $site_terms
	 * @param int    $term_taxonomy_id
	 *
	 * @return void
	 */
	public function test_get_current_term(
		$expected,
		$site_id,
		$term_id,
		$term,
		array $site_terms,
		$term_taxonomy_id
	) {

		$current_blog_id = 42;

		/** @var Mlp_Content_Relations_Interface $content_relations */
		$content_relations = Mockery::mock( 'Mlp_Content_Relations_Interface' );
		$content_relations->shouldReceive( 'get_element_for_site' )
			->with(
				$current_blog_id,
				$site_id,
				Mockery::type( 'int' ),
				'term'
			)
			->andReturn( $term_taxonomy_id );

		/** @var Inpsyde_Nonce_Validator_Interface $nonce */
		$nonce = Mockery::mock( 'Inpsyde_Nonce_Validator_Interface' );

		$key_base = 'key_base';

		WP_Mock::wpFunction(
			'get_current_blog_id',
			array(
				'times'  => 1,
				'return' => $current_blog_id,
			)
		);

		$taxonomy = 'taxonomy';

		WP_Mock::wpFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => (object) array( 'taxonomy' => $taxonomy ),
			)
		);

		$testee = new Mlp_Term_Translation_Presenter(
			$content_relations,
			$nonce,
			$key_base
		);

		WP_mock::wpFunction(
			'switch_to_blog',
			array(
				'times' => 1,
				'args'  => array(
					$current_blog_id,
				),
			)
		);

		WP_mock::wpFunction(
			'get_term_by',
			array(
				'times'  => 1,
				'args'   => array(
					'id',
					Mockery::type( 'int' ),
					$taxonomy,
				),
				'return' => $term,
			)
		);

		WP_mock::wpFunction(
			'restore_current_blog',
			array(
				'times' => 1,
			)
		);

		// Hack the private property and set its value according to the current test data set
		$reflector = new ReflectionProperty( 'Mlp_Term_Translation_Presenter', 'site_terms' );
		$reflector->setAccessible( TRUE );
		$reflector->setValue( $testee, $site_terms );

		$this->assertSame( $expected, $testee->get_current_term( $site_id, $term_id ) );
	}

	/**
	 * Data provider for the test_get_current_term() method.
	 *
	 * @return array
	 */
	public function provide_get_current_term_data() {

		$site_id = 1;

		$term_id = 23;

		$term_taxonomy_id = 69;

		$term = (object) array(
			'term_taxonomy_id' => $term_taxonomy_id,
		);

		$remote_term_taxonomy_id = 77;

		return array(
			'no_term'             => array(
				'expected'         => 0,
				'site_id'          => $site_id,
				'term_id'          => $term_id,
				'term'             => NULL,
				'site_terms'       => array(),
				'term_taxonomy_id' => 0,
			),
			'no_element_for_site' => array(
				'expected'         => 0,
				'site_id'          => $site_id,
				'term_id'          => $term_id,
				'term'             => $term,
				'site_terms'       => array(),
				'term_taxonomy_id' => 0,
			),
			'element_for_site'    => array(
				'expected'         => $remote_term_taxonomy_id,
				'site_id'          => $site_id,
				'term_id'          => $term_id,
				'term'             => $term,
				'site_terms'       => array(),
				'term_taxonomy_id' => $remote_term_taxonomy_id,
			),
			'cached_term'         => array(
				'expected'         => $remote_term_taxonomy_id,
				'site_id'          => $site_id,
				'term_id'          => $term_id,
				'term'             => $term,
				'site_terms'       => array( $term_taxonomy_id => array( $site_id => $remote_term_taxonomy_id ) ),
				'term_taxonomy_id' => $term_taxonomy_id,
			),
		);
	}

	/**
	 * @covers       Mlp_Term_Translation_Presenter::get_relation_id
	 * @dataProvider provide_get_relation_id_data
	 *
	 * @param string $expected
	 * @param int    $site_id
	 * @param int    $term_taxonomy_id
	 * @param array  $translation_ids
	 *
	 * @return void
	 */
	public function test_get_relation_id(
		$expected,
		$site_id,
		$term_taxonomy_id,
		array $translation_ids
	) {

		/** @var Mlp_Content_Relations_Interface $content_relations */
		$content_relations = Mockery::mock( 'Mlp_Content_Relations_Interface' );
		$content_relations->shouldReceive( 'get_existing_translation_ids' )
			->once()
			->with(
				$site_id,
				0,
				$term_taxonomy_id,
				0,
				'term'
			)
			->andReturn( $translation_ids );

		/** @var Inpsyde_Nonce_Validator_Interface $nonce */
		$nonce = Mockery::mock( 'Inpsyde_Nonce_Validator_Interface' );

		$key_base = 'key_base';

		WP_Mock::wpFunction(
			'get_current_blog_id',
			array(
				'times'  => 1,
				'return' => 42,
			)
		);

		WP_Mock::wpFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => (object) array( 'taxonomy' => 'taxonomy' ),
			)
		);

		$testee = new Mlp_Term_Translation_Presenter(
			$content_relations,
			$nonce,
			$key_base
		);

		$this->assertSame( $expected, $testee->get_relation_id( $site_id, $term_taxonomy_id ) );
	}

	/**
	 * Provider for the test_get_relation_id() method.
	 *
	 * @return array
	 */
	public function provide_get_relation_id_data() {

		$ml_source_blogid = 'ml_source_blogid';

		$ml_source_elementid = 'ml_source_elementid';

		$translation_ids = array(
			compact(
				'ml_source_blogid',
				'ml_source_elementid'
			),
		);

		return array(
			'translation_ids' => array(
				'expected'         => '',
				'site_id'          => 1,
				'term_taxonomy_id' => 42,
				'translation_ids'  => array(),
			),
			'some_languages'  => array(
				'expected'         => "$ml_source_blogid-$ml_source_elementid",
				'site_id'          => 1,
				'term_taxonomy_id' => 42,
				'translation_ids'  => $translation_ids,
			),
		);
	}

}
