<?php
namespace DynamicVisibilityForElementor;

class DCE_Query extends \WP_Query {

	/**
	 * Retrieves an array of posts based on query variables.
	 *
	 * There are a few filters and actions that can be used to modify the post
	 * database query.
	 *
	 * @since 1.5.0
	 *
	 * @return WP_Post[]|int[] Array of post objects or post IDs.
	 */
	public function get_posts() {
		global $wpdb;

		$this->parse_query();

		/**
		 * Fires after the query variable object is created, but before the actual query is run.
		 *
		 * Note: If using conditional tags, use the method versions within the passed instance
		 * (e.g. $this->is_main_query() instead of is_main_query()). This is because the functions
		 * like is_main_query() test against the global $wp_query instance, not the passed one.
		 *
		 * @since 2.0.0
		 *
		 * @param WP_Query $this The WP_Query instance (passed by reference).
		 */
		do_action_ref_array( 'pre_get_posts', array( &$this ) );

		// Shorthand.
		$q = &$this->query_vars;

		// Fill again in case pre_get_posts unset some vars.
		$q = $this->fill_query_vars( $q );

		// Parse meta query
		$this->meta_query = new \WP_Meta_Query();
		$this->meta_query->parse_query_vars( $q );

		// Set a flag if a pre_get_posts hook changed the query vars.
		$hash = md5( serialize( $this->query_vars ) );
		if ( $hash != $this->query_vars_hash ) {
			$this->query_vars_changed = true;
			$this->query_vars_hash    = $hash;
		}
		unset( $hash );

		// First let's clear some variables
		$distinct         = '';
		$whichauthor      = '';
		$whichmimetype    = '';
		$where            = '';
		$limits           = '';
		$join             = '';
		$search           = '';
		$groupby          = '';
		$post_status_join = false;
		$page             = 1;

		if ( isset( $q['caller_get_posts'] ) ) {
			_deprecated_argument(
				'WP_Query',
				'3.1.0',
				/* translators: 1: caller_get_posts, 2: ignore_sticky_posts */
				sprintf(
					__( '%1$s is deprecated. Use %2$s instead.', 'dynamic-visibility-for-elementor' ),
					'<code>caller_get_posts</code>',
					'<code>ignore_sticky_posts</code>'
				)
			);

			if ( ! isset( $q['ignore_sticky_posts'] ) ) {
				$q['ignore_sticky_posts'] = $q['caller_get_posts'];
			}
		}

		if ( ! isset( $q['ignore_sticky_posts'] ) ) {
			$q['ignore_sticky_posts'] = false;
		}

		if ( ! isset( $q['suppress_filters'] ) ) {
			$q['suppress_filters'] = false;
		}

		if ( ! isset( $q['cache_results'] ) ) {
			if ( wp_using_ext_object_cache() ) {
				$q['cache_results'] = false;
			} else {
				$q['cache_results'] = true;
			}
		}

		if ( ! isset( $q['update_post_term_cache'] ) ) {
			$q['update_post_term_cache'] = true;
		}

		if ( ! isset( $q['lazy_load_term_meta'] ) ) {
			$q['lazy_load_term_meta'] = $q['update_post_term_cache'];
		}

		if ( ! isset( $q['update_post_meta_cache'] ) ) {
			$q['update_post_meta_cache'] = true;
		}

		if ( ! isset( $q['post_type'] ) ) {
			if ( $this->is_search ) {
				$q['post_type'] = 'any';
			} else {
				$q['post_type'] = '';
			}
		}
		$post_type = $q['post_type'];
		if ( empty( $q['posts_per_page'] ) ) {
			$q['posts_per_page'] = get_option( 'posts_per_page' );
		}
		if ( isset( $q['showposts'] ) && $q['showposts'] ) {
			$q['showposts']      = (int) $q['showposts'];
			$q['posts_per_page'] = $q['showposts'];
		}
		if ( ( isset( $q['posts_per_archive_page'] ) && $q['posts_per_archive_page'] != 0 ) && ( $this->is_archive || $this->is_search ) ) {
			$q['posts_per_page'] = $q['posts_per_archive_page'];
		}
		if ( ! isset( $q['nopaging'] ) ) {
			if ( $q['posts_per_page'] == -1 ) {
				$q['nopaging'] = true;
			} else {
				$q['nopaging'] = false;
			}
		}

		if ( $this->is_feed ) {
			// This overrides posts_per_page.
			if ( ! empty( $q['posts_per_rss'] ) ) {
				$q['posts_per_page'] = $q['posts_per_rss'];
			} else {
				$q['posts_per_page'] = get_option( 'posts_per_rss' );
			}
			$q['nopaging'] = false;
		}
		$q['posts_per_page'] = (int) $q['posts_per_page'];
		if ( $q['posts_per_page'] < -1 ) {
			$q['posts_per_page'] = abs( $q['posts_per_page'] );
		} elseif ( $q['posts_per_page'] == 0 ) {
			$q['posts_per_page'] = 1;
		}

		if ( ! isset( $q['comments_per_page'] ) || $q['comments_per_page'] == 0 ) {
			$q['comments_per_page'] = get_option( 'comments_per_page' );
		}

		if ( $this->is_home && ( empty( $this->query ) || $q['preview'] == 'true' ) && ( 'page' == get_option( 'show_on_front' ) ) && get_option( 'page_on_front' ) ) {
			$this->is_page = true;
			$this->is_home = false;
			$q['page_id']  = get_option( 'page_on_front' );
		}

		if ( isset( $q['page'] ) ) {
			$q['page'] = trim( $q['page'], '/' );
			$q['page'] = absint( $q['page'] );
		}

		// If true, forcibly turns off SQL_CALC_FOUND_ROWS even when limits are present.
		if ( isset( $q['no_found_rows'] ) ) {
			$q['no_found_rows'] = (bool) $q['no_found_rows'];
		} else {
			$q['no_found_rows'] = false;
		}

		switch ( $q['fields'] ) {
			case 'ids':
				$fields = "{$wpdb->posts}.ID";
				break;
			case 'id=>parent':
				$fields = "{$wpdb->posts}.ID, {$wpdb->posts}.post_parent";
				break;
			default:
				$fields = "{$wpdb->posts}.*";
		}

		if ( '' !== $q['menu_order'] ) {
			$where .= " AND {$wpdb->posts}.menu_order = " . $q['menu_order'];
		}
		// The "m" parameter is meant for months but accepts datetimes of varying specificity
		if ( $q['m'] ) {
			$where .= " AND YEAR({$wpdb->posts}.post_date)=" . substr( $q['m'], 0, 4 );
			if ( strlen( $q['m'] ) > 5 ) {
				$where .= " AND MONTH({$wpdb->posts}.post_date)=" . substr( $q['m'], 4, 2 );
			}
			if ( strlen( $q['m'] ) > 7 ) {
				$where .= " AND DAYOFMONTH({$wpdb->posts}.post_date)=" . substr( $q['m'], 6, 2 );
			}
			if ( strlen( $q['m'] ) > 9 ) {
				$where .= " AND HOUR({$wpdb->posts}.post_date)=" . substr( $q['m'], 8, 2 );
			}
			if ( strlen( $q['m'] ) > 11 ) {
				$where .= " AND MINUTE({$wpdb->posts}.post_date)=" . substr( $q['m'], 10, 2 );
			}
			if ( strlen( $q['m'] ) > 13 ) {
				$where .= " AND SECOND({$wpdb->posts}.post_date)=" . substr( $q['m'], 12, 2 );
			}
		}

		// Handle the other individual date parameters
		$date_parameters = array();

		if ( '' !== $q['hour'] ) {
			$date_parameters['hour'] = $q['hour'];
		}

		if ( '' !== $q['minute'] ) {
			$date_parameters['minute'] = $q['minute'];
		}

		if ( '' !== $q['second'] ) {
			$date_parameters['second'] = $q['second'];
		}

		if ( $q['year'] ) {
			$date_parameters['year'] = $q['year'];
		}

		if ( $q['monthnum'] ) {
			$date_parameters['monthnum'] = $q['monthnum'];
		}

		if ( $q['w'] ) {
			$date_parameters['week'] = $q['w'];
		}

		if ( $q['day'] ) {
			$date_parameters['day'] = $q['day'];
		}

		if ( $date_parameters ) {
			$date_query = new \WP_Date_Query( array( $date_parameters ) );
			$where     .= $date_query->get_sql();
		}
		unset( $date_parameters, $date_query );

		// Handle complex date queries
		if ( ! empty( $q['date_query'] ) ) {
			$this->date_query = new \WP_Date_Query( $q['date_query'] );
			$where           .= $this->date_query->get_sql();
		}

		// If we've got a post_type AND it's not "any" post_type.
		if ( ! empty( $q['post_type'] ) && 'any' != $q['post_type'] ) {
			foreach ( (array) $q['post_type'] as $_post_type ) {
				$ptype_obj = get_post_type_object( $_post_type );
				if ( ! $ptype_obj || ! $ptype_obj->query_var || empty( $q[ $ptype_obj->query_var ] ) ) {
					continue;
				}

				if ( ! $ptype_obj->hierarchical ) {
					// Non-hierarchical post types can directly use 'name'.
					$q['name'] = $q[ $ptype_obj->query_var ];
				} else {
					// Hierarchical post types will operate through 'pagename'.
					$q['pagename'] = $q[ $ptype_obj->query_var ];
					$q['name']     = '';
				}

				// Only one request for a slug is possible, this is why name & pagename are overwritten above.
				break;
			} //end foreach
			unset( $ptype_obj );
		}

		if ( '' !== $q['title'] ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title = %s", stripslashes( $q['title'] ) );
		}

		// Parameters related to 'post_name'.
		if ( '' != $q['name'] ) {
			$q['name'] = sanitize_title_for_query( $q['name'] );
			$where    .= " AND {$wpdb->posts}.post_name = '" . $q['name'] . "'";
		} elseif ( '' != $q['pagename'] ) {
			if ( isset( $this->queried_object_id ) ) {
				$reqpage = $this->queried_object_id;
			} else {
				if ( 'page' != $q['post_type'] ) {
					foreach ( (array) $q['post_type'] as $_post_type ) {
						$ptype_obj = get_post_type_object( $_post_type );
						if ( ! $ptype_obj || ! $ptype_obj->hierarchical ) {
							continue;
						}

						$reqpage = get_page_by_path( $q['pagename'], OBJECT, $_post_type );
						if ( $reqpage ) {
							break;
						}
					}
					unset( $ptype_obj );
				} else {
					$reqpage = get_page_by_path( $q['pagename'] );
				}
				if ( ! empty( $reqpage ) ) {
					$reqpage = $reqpage->ID;
				} else {
					$reqpage = 0;
				}
			}

			$page_for_posts = get_option( 'page_for_posts' );
			if ( ( 'page' != get_option( 'show_on_front' ) ) || empty( $page_for_posts ) || ( $reqpage != $page_for_posts ) ) {
				$q['pagename'] = sanitize_title_for_query( wp_basename( $q['pagename'] ) );
				$q['name']     = $q['pagename'];
				$where        .= " AND ({$wpdb->posts}.ID = '$reqpage')";
				$reqpage_obj   = get_post( $reqpage );
				if ( is_object( $reqpage_obj ) && 'attachment' == $reqpage_obj->post_type ) {
					$this->is_attachment = true;
					$post_type           = $q['post_type'] = 'attachment';
					$this->is_page       = true;
					$q['attachment_id']  = $reqpage;
				}
			}
		} elseif ( '' != $q['attachment'] ) {
			$q['attachment'] = sanitize_title_for_query( wp_basename( $q['attachment'] ) );
			$q['name']       = $q['attachment'];
			$where          .= " AND {$wpdb->posts}.post_name = '" . $q['attachment'] . "'";
		} elseif ( is_array( $q['post_name__in'] ) && ! empty( $q['post_name__in'] ) ) {
			$q['post_name__in'] = array_map( 'sanitize_title_for_query', $q['post_name__in'] );
			$post_name__in      = "'" . implode( "','", $q['post_name__in'] ) . "'";
			$where             .= " AND {$wpdb->posts}.post_name IN ($post_name__in)";
		}

		// If an attachment is requested by number, let it supersede any post number.
		if ( $q['attachment_id'] ) {
			$q['p'] = absint( $q['attachment_id'] );
		}

		// If a post number is specified, load that post
		if ( $q['p'] ) {
			$where .= " AND {$wpdb->posts}.ID = " . $q['p'];
		} elseif ( $q['post__in'] ) {
			$post__in = implode( ',', array_map( 'absint', $q['post__in'] ) );
			$where   .= " AND {$wpdb->posts}.ID IN ($post__in)";
		} elseif ( $q['post__not_in'] ) {
			$post__not_in = implode( ',', array_map( 'absint', $q['post__not_in'] ) );
			$where       .= " AND {$wpdb->posts}.ID NOT IN ($post__not_in)";
		}

		if ( is_numeric( $q['post_parent'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_parent = %d ", $q['post_parent'] );
		} elseif ( $q['post_parent__in'] ) {
			$post_parent__in = implode( ',', array_map( 'absint', $q['post_parent__in'] ) );
			$where          .= " AND {$wpdb->posts}.post_parent IN ($post_parent__in)";
		} elseif ( $q['post_parent__not_in'] ) {
			$post_parent__not_in = implode( ',', array_map( 'absint', $q['post_parent__not_in'] ) );
			$where              .= " AND {$wpdb->posts}.post_parent NOT IN ($post_parent__not_in)";
		}

		if ( $q['page_id'] ) {
			if ( ( 'page' != get_option( 'show_on_front' ) ) || ( $q['page_id'] != get_option( 'page_for_posts' ) ) ) {
				$q['p'] = $q['page_id'];
				$where  = " AND {$wpdb->posts}.ID = " . $q['page_id'];
			}
		}

		// If a search pattern is specified, load the posts that match.
		if ( strlen( $q['s'] ) ) {
			$search = $this->parse_search( $q );
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the search SQL that is used in the WHERE clause of WP_Query.
			 *
			 * @since 3.0.0
			 *
			 * @param string   $search Search SQL for WHERE clause.
			 * @param WP_Query $this   The current WP_Query object.
			 */
			$search = apply_filters_ref_array( 'posts_search', array( $search, &$this ) );
		}

		// Taxonomies
		if ( ! $this->is_singular ) {
			$this->parse_tax_query( $q );

			$clauses = $this->tax_query->get_sql( $wpdb->posts, 'ID' );

			$join  .= $clauses['join'];
			$where .= $clauses['where'];
		}

		if ( $this->is_tax ) {
			if ( empty( $post_type ) ) {
				// Do a fully inclusive search for currently registered post types of queried taxonomies
				$post_type  = array();
				$taxonomies = array_keys( $this->tax_query->queried_terms );
				//foreach ( get_post_types( array( 'exclude_from_search' => false ) ) as $pt ) { // DCE
				foreach ( get_post_types() as $pt ) {
					$object_taxonomies = $pt === 'attachment' ? get_taxonomies_for_attachments() : get_object_taxonomies( $pt );
					if ( array_intersect( $taxonomies, $object_taxonomies ) ) {
						$post_type[] = $pt;
					}
				}
				if ( ! $post_type ) {
					$post_type = 'any';
				} elseif ( count( $post_type ) == 1 ) {
					$post_type = $post_type[0];
				}

				$post_status_join = true;
			} elseif ( in_array( 'attachment', (array) $post_type ) ) {
				$post_status_join = true;
			}
		}

		/*
		 * Ensure that 'taxonomy', 'term', 'term_id', 'cat', and
		 * 'category_name' vars are set for backward compatibility.
		 */
		if ( ! empty( $this->tax_query->queried_terms ) ) {

			/*
			 * Set 'taxonomy', 'term', and 'term_id' to the
			 * first taxonomy other than 'post_tag' or 'category'.
			 */
			if ( ! isset( $q['taxonomy'] ) ) {
				foreach ( $this->tax_query->queried_terms as $queried_taxonomy => $queried_items ) {
					if ( empty( $queried_items['terms'][0] ) ) {
						continue;
					}

					if ( ! in_array( $queried_taxonomy, array( 'category', 'post_tag' ) ) ) {
						$q['taxonomy'] = $queried_taxonomy;

						if ( 'slug' === $queried_items['field'] ) {
							$q['term'] = $queried_items['terms'][0];
						} else {
							$q['term_id'] = $queried_items['terms'][0];
						}

						// Take the first one we find.
						break;
					}
				}
			}

			// 'cat', 'category_name', 'tag_id'
			foreach ( $this->tax_query->queried_terms as $queried_taxonomy => $queried_items ) {
				if ( empty( $queried_items['terms'][0] ) ) {
					continue;
				}

				if ( 'category' === $queried_taxonomy ) {
					$the_cat = get_term_by( $queried_items['field'], $queried_items['terms'][0], 'category' );
					if ( $the_cat ) {
						$this->set( 'cat', $the_cat->term_id );
						$this->set( 'category_name', $the_cat->slug );
					}
					unset( $the_cat );
				}

				if ( 'post_tag' === $queried_taxonomy ) {
					$the_tag = get_term_by( $queried_items['field'], $queried_items['terms'][0], 'post_tag' );
					if ( $the_tag ) {
						$this->set( 'tag_id', $the_tag->term_id );
					}
					unset( $the_tag );
				}
			}
		}

		if ( ! empty( $this->tax_query->queries ) || ! empty( $this->meta_query->queries ) ) {
			$groupby = "{$wpdb->posts}.ID";
		}

		// Author/user stuff

		if ( ! empty( $q['author'] ) && $q['author'] != '0' ) {
			$q['author'] = addslashes_gpc( '' . urldecode( $q['author'] ) );
			$authors     = array_unique( array_map( 'intval', preg_split( '/[,\s]+/', $q['author'] ) ) );
			foreach ( $authors as $author ) {
				$key         = $author > 0 ? 'author__in' : 'author__not_in';
				$q[ $key ][] = abs( $author );
			}
			$q['author'] = implode( ',', $authors );
		}

		if ( ! empty( $q['author__not_in'] ) ) {
			$author__not_in = implode( ',', array_map( 'absint', array_unique( (array) $q['author__not_in'] ) ) );
			$where         .= " AND {$wpdb->posts}.post_author NOT IN ($author__not_in) ";
		} elseif ( ! empty( $q['author__in'] ) ) {
			$author__in = implode( ',', array_map( 'absint', array_unique( (array) $q['author__in'] ) ) );
			$where     .= " AND {$wpdb->posts}.post_author IN ($author__in) ";
		}

		// Author stuff for nice URLs

		if ( '' != $q['author_name'] ) {
			if ( strpos( $q['author_name'], '/' ) !== false ) {
				$q['author_name'] = explode( '/', $q['author_name'] );
				if ( $q['author_name'][ count( $q['author_name'] ) - 1 ] ) {
					$q['author_name'] = $q['author_name'][ count( $q['author_name'] ) - 1 ]; // no trailing slash
				} else {
					$q['author_name'] = $q['author_name'][ count( $q['author_name'] ) - 2 ]; // there was a trailing slash
				}
			}
			$q['author_name'] = sanitize_title_for_query( $q['author_name'] );
			$q['author']      = get_user_by( 'slug', $q['author_name'] );
			if ( $q['author'] ) {
				$q['author'] = $q['author']->ID;
			}
			$whichauthor .= " AND ({$wpdb->posts}.post_author = " . absint( $q['author'] ) . ')';
		}

		// Matching by comment count.
		if ( isset( $q['comment_count'] ) ) {
			// Numeric comment count is converted to array format.
			if ( is_numeric( $q['comment_count'] ) ) {
				$q['comment_count'] = array(
					'value' => intval( $q['comment_count'] ),
				);
			}

			if ( isset( $q['comment_count']['value'] ) ) {
				$q['comment_count'] = array_merge(
					array(
						'compare' => '=',
					),
					$q['comment_count']
				);

				// Fallback for invalid compare operators is '='.
				$compare_operators = array( '=', '!=', '>', '>=', '<', '<=' );
				if ( ! in_array( $q['comment_count']['compare'], $compare_operators, true ) ) {
					$q['comment_count']['compare'] = '=';
				}

				$where .= $wpdb->prepare( " AND {$wpdb->posts}.comment_count {$q['comment_count']['compare']} %d", $q['comment_count']['value'] );
			}
		}

		// MIME-Type stuff for attachment browsing

		if ( isset( $q['post_mime_type'] ) && '' != $q['post_mime_type'] ) {
			$whichmimetype = wp_post_mime_type_where( $q['post_mime_type'], $wpdb->posts );
		}
		$where .= $search . $whichauthor . $whichmimetype;

		if ( ! empty( $this->meta_query->queries ) ) {
			$clauses = $this->meta_query->get_sql( 'post', $wpdb->posts, 'ID', $this );
			$join   .= $clauses['join'];
			$where  .= $clauses['where'];
		}

		$rand = ( isset( $q['orderby'] ) && 'rand' === $q['orderby'] );
		if ( ! isset( $q['order'] ) ) {
			$q['order'] = $rand ? '' : 'DESC';
		} else {
			$q['order'] = $rand ? '' : $this->parse_order( $q['order'] );
		}

		// These values of orderby should ignore the 'order' parameter.
		$force_asc = array( 'post__in', 'post_name__in', 'post_parent__in' );
		if ( isset( $q['orderby'] ) && in_array( $q['orderby'], $force_asc, true ) ) {
			$q['order'] = '';
		}

		// Order by.
		if ( empty( $q['orderby'] ) ) {
			/*
			 * Boolean false or empty array blanks out ORDER BY,
			 * while leaving the value unset or otherwise empty sets the default.
			 */
			if ( isset( $q['orderby'] ) && ( is_array( $q['orderby'] ) || false === $q['orderby'] ) ) {
				$orderby = '';
			} else {
				$orderby = "{$wpdb->posts}.post_date " . $q['order'];
			}
		} elseif ( 'none' == $q['orderby'] ) {
			$orderby = '';
		} else {
			$orderby_array = array();
			if ( is_array( $q['orderby'] ) ) {
				foreach ( $q['orderby'] as $_orderby => $order ) {
					$orderby = addslashes_gpc( urldecode( $_orderby ) );
					$parsed  = $this->parse_orderby( $orderby );

					if ( ! $parsed ) {
						continue;
					}

					$orderby_array[] = $parsed . ' ' . $this->parse_order( $order );
				}
				$orderby = implode( ', ', $orderby_array );

			} else {
				$q['orderby'] = urldecode( $q['orderby'] );
				$q['orderby'] = addslashes_gpc( $q['orderby'] );

				foreach ( explode( ' ', $q['orderby'] ) as $i => $orderby ) {
					$parsed = $this->parse_orderby( $orderby );
					// Only allow certain values for safety.
					if ( ! $parsed ) {
						continue;
					}

					$orderby_array[] = $parsed;
				}
				$orderby = implode( ' ' . $q['order'] . ', ', $orderby_array );

				if ( empty( $orderby ) ) {
					$orderby = "{$wpdb->posts}.post_date " . $q['order'];
				} elseif ( ! empty( $q['order'] ) ) {
					$orderby .= " {$q['order']}";
				}
			}
		}

		// Order search results by relevance only when another "orderby" is not specified in the query.
		if ( ! empty( $q['s'] ) ) {
			$search_orderby = '';
			if ( ! empty( $q['search_orderby_title'] ) && ( empty( $q['orderby'] ) && ! $this->is_feed ) || ( isset( $q['orderby'] ) && 'relevance' === $q['orderby'] ) ) {
				$search_orderby = $this->parse_search_order( $q );
			}

			if ( ! $q['suppress_filters'] ) {
				/**
				 * Filters the ORDER BY used when ordering search results.
				 *
				 * @since 3.7.0
				 *
				 * @param string   $search_orderby The ORDER BY clause.
				 * @param WP_Query $this           The current WP_Query instance.
				 */
				$search_orderby = apply_filters( 'posts_search_orderby', $search_orderby, $this );
			}

			if ( $search_orderby ) {
				$orderby = $orderby ? $search_orderby . ', ' . $orderby : $search_orderby;
			}
		}

		if ( is_array( $post_type ) && count( $post_type ) > 1 ) {
			$post_type_cap = 'multiple_post_type';
		} else {
			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}
			$post_type_object = get_post_type_object( $post_type );
			if ( empty( $post_type_object ) ) {
				$post_type_cap = $post_type;
			}
		}

		if ( isset( $q['post_password'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_password = %s", $q['post_password'] );
			if ( empty( $q['perm'] ) ) {
				$q['perm'] = 'readable';
			}
		} elseif ( isset( $q['has_password'] ) ) {
			$where .= sprintf( " AND {$wpdb->posts}.post_password %s ''", $q['has_password'] ? '!=' : '=' );
		}

		if ( ! empty( $q['comment_status'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.comment_status = %s ", $q['comment_status'] );
		}

		if ( ! empty( $q['ping_status'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.ping_status = %s ", $q['ping_status'] );
		}

		if ( 'any' == $post_type ) {
			//$in_search_post_types = get_post_types( array( 'exclude_from_search' => false ) ); // DCE
						$in_search_post_types = get_post_types();
			if ( empty( $in_search_post_types ) ) {
				$where .= ' AND 1=0 ';
			} else {
				$where .= " AND {$wpdb->posts}.post_type IN ('" . join( "', '", array_map( 'esc_sql', $in_search_post_types ) ) . "')";
			}
		} elseif ( ! empty( $post_type ) && is_array( $post_type ) ) {
			$where .= " AND {$wpdb->posts}.post_type IN ('" . join( "', '", esc_sql( $post_type ) ) . "')";
		} elseif ( ! empty( $post_type ) ) {
			$where           .= $wpdb->prepare( " AND {$wpdb->posts}.post_type = %s", $post_type );
			$post_type_object = get_post_type_object( $post_type );
		} elseif ( $this->is_attachment ) {
			$where           .= " AND {$wpdb->posts}.post_type = 'attachment'";
			$post_type_object = get_post_type_object( 'attachment' );
		} elseif ( $this->is_page ) {
			$where           .= " AND {$wpdb->posts}.post_type = 'page'";
			$post_type_object = get_post_type_object( 'page' );
		} else {
			$where           .= " AND {$wpdb->posts}.post_type = 'post'";
			$post_type_object = get_post_type_object( 'post' );
		}

		$edit_cap = 'edit_post';
		$read_cap = 'read_post';

		if ( ! empty( $post_type_object ) ) {
			$edit_others_cap  = $post_type_object->cap->edit_others_posts;
			$read_private_cap = $post_type_object->cap->read_private_posts;
		} else {
			$edit_others_cap  = 'edit_others_' . $post_type_cap . 's';
			$read_private_cap = 'read_private_' . $post_type_cap . 's';
		}

		$user_id = get_current_user_id();

		$q_status = array();
		if ( ! empty( $q['post_status'] ) ) {
			$statuswheres = array();
			$q_status     = $q['post_status'];
			if ( ! is_array( $q_status ) ) {
				$q_status = explode( ',', $q_status );
			}
			$r_status = array();
			$p_status = array();
			$e_status = array();
			// DCE
			foreach ( get_post_statuses() as $status ) {
				if ( in_array( $status, $q_status ) ) {
					if ( 'private' == $status ) {
						$p_status[] = "{$wpdb->posts}.post_status = '$status'";
					} else {
						$r_status[] = "{$wpdb->posts}.post_status = '$status'";
					}
				}
			}

			if ( empty( $q['perm'] ) || 'readable' != $q['perm'] ) {
				$r_status = array_merge( $r_status, $p_status );
				unset( $p_status );
			}

			if ( ! empty( $e_status ) ) {
				$statuswheres[] = '(' . join( ' AND ', $e_status ) . ')';
			}
			if ( ! empty( $r_status ) ) {
				if ( ! empty( $q['perm'] ) && 'editable' == $q['perm'] && ! current_user_can( $edit_others_cap ) ) {
					$statuswheres[] = "({$wpdb->posts}.post_author = $user_id " . 'AND (' . join( ' OR ', $r_status ) . '))';
				} else {
					$statuswheres[] = '(' . join( ' OR ', $r_status ) . ')';
				}
			}
			if ( ! empty( $p_status ) ) {
				if ( ! empty( $q['perm'] ) && 'readable' == $q['perm'] && ! current_user_can( $read_private_cap ) ) {
					$statuswheres[] = "({$wpdb->posts}.post_author = $user_id " . 'AND (' . join( ' OR ', $p_status ) . '))';
				} else {
					$statuswheres[] = '(' . join( ' OR ', $p_status ) . ')';
				}
			}
			if ( $post_status_join ) {
				$join .= " LEFT JOIN {$wpdb->posts} AS p2 ON ({$wpdb->posts}.post_parent = p2.ID) ";
				foreach ( $statuswheres as $index => $statuswhere ) {
					$statuswheres[ $index ] = "($statuswhere OR ({$wpdb->posts}.post_status = 'inherit' AND " . str_replace( $wpdb->posts, 'p2', $statuswhere ) . '))';
				}
			}
			$where_status = implode( ' OR ', $statuswheres );
			if ( ! empty( $where_status ) ) {
				$where .= " AND ($where_status)";
			}
		} elseif ( ! $this->is_singular ) {
			$where .= " AND ({$wpdb->posts}.post_status = 'publish'";

			// Add public states.
			$public_states = get_post_statuses( array( 'public' => true ) );
			foreach ( (array) $public_states as $state ) {
				if ( 'publish' == $state ) { // Publish is hard-coded above.
					continue;
				}
				$where .= " OR {$wpdb->posts}.post_status = '$state'";
			}

			if ( $this->is_admin ) {
				// Add protected states that should show in the admin all list.
				$admin_all_states = get_post_statuses(
					array(
						'protected'              => true,
						'show_in_admin_all_list' => true,
					)
				);
				foreach ( (array) $admin_all_states as $state ) {
					$where .= " OR {$wpdb->posts}.post_status = '$state'";
				}
			}

			if ( is_user_logged_in() ) {
				// Add private states that are limited to viewing by the author of a post or someone who has caps to read private states.
				$private_states = get_post_statuses( array( 'private' => true ) );
				foreach ( (array) $private_states as $state ) {
					$where .= current_user_can( $read_private_cap ) ? " OR {$wpdb->posts}.post_status = '$state'" : " OR {$wpdb->posts}.post_author = $user_id AND {$wpdb->posts}.post_status = '$state'";
				}
			}

			$where .= ')';
		}

		/*
		 * Apply filters on where and join prior to paging so that any
		 * manipulations to them are reflected in the paging by day queries.
		 */
		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where', array( $where, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join', array( $join, &$this ) );
		}

		// Paging
		if ( empty( $q['nopaging'] ) && ! $this->is_singular ) {
			$page = absint( $q['paged'] );
			if ( ! $page ) {
				$page = 1;
			}

			// If 'offset' is provided, it takes precedence over 'paged'.
			if ( isset( $q['offset'] ) && is_numeric( $q['offset'] ) ) {
				$q['offset'] = absint( $q['offset'] );
				$pgstrt      = $q['offset'] . ', ';
			} else {
				$pgstrt = absint( ( $page - 1 ) * $q['posts_per_page'] ) . ', ';
			}
			$limits = 'LIMIT ' . $pgstrt . $q['posts_per_page'];
		}

		// Comments feeds
		if ( $this->is_comment_feed && ! $this->is_singular ) {
			if ( $this->is_archive || $this->is_search ) {
				$cjoin    = "JOIN {$wpdb->posts} ON ({$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID) $join ";
				$cwhere   = "WHERE comment_approved = '1' $where";
				$cgroupby = "{$wpdb->comments}.comment_id";
			} else { // Other non singular e.g. front
				$cjoin    = "JOIN {$wpdb->posts} ON ( {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID )";
				$cwhere   = "WHERE ( post_status = 'publish' OR ( post_status = 'inherit' AND post_type = 'attachment' ) ) AND comment_approved = '1'";
				$cgroupby = '';
			}

			if ( ! $q['suppress_filters'] ) {
				/**
				 * Filters the JOIN clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cjoin The JOIN clause of the query.
				 * @param WP_Query $this The WP_Query instance (passed by reference).
				 */
				$cjoin = apply_filters_ref_array( 'comment_feed_join', array( $cjoin, &$this ) );

				/**
				 * Filters the WHERE clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cwhere The WHERE clause of the query.
				 * @param WP_Query $this   The WP_Query instance (passed by reference).
				 */
				$cwhere = apply_filters_ref_array( 'comment_feed_where', array( $cwhere, &$this ) );

				/**
				 * Filters the GROUP BY clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cgroupby The GROUP BY clause of the query.
				 * @param WP_Query $this     The WP_Query instance (passed by reference).
				 */
				$cgroupby = apply_filters_ref_array( 'comment_feed_groupby', array( $cgroupby, &$this ) );

				/**
				 * Filters the ORDER BY clause of the comments feed query before sending.
				 *
				 * @since 2.8.0
				 *
				 * @param string   $corderby The ORDER BY clause of the query.
				 * @param WP_Query $this     The WP_Query instance (passed by reference).
				 */
				$corderby = apply_filters_ref_array( 'comment_feed_orderby', array( 'comment_date_gmt DESC', &$this ) );

				/**
				 * Filters the LIMIT clause of the comments feed query before sending.
				 *
				 * @since 2.8.0
				 *
				 * @param string   $climits The JOIN clause of the query.
				 * @param WP_Query $this    The WP_Query instance (passed by reference).
				 */
				$climits = apply_filters_ref_array( 'comment_feed_limits', array( 'LIMIT ' . get_option( 'posts_per_rss' ), &$this ) );
			}
			$cgroupby = ( ! empty( $cgroupby ) ) ? 'GROUP BY ' . $cgroupby : '';
			$corderby = ( ! empty( $corderby ) ) ? 'ORDER BY ' . $corderby : '';

			$comments = (array) $wpdb->get_results( "SELECT $distinct {$wpdb->comments}.* FROM {$wpdb->comments} $cjoin $cwhere $cgroupby $corderby $climits" );
			// Convert to WP_Comment
			$this->comments      = array_map( 'get_comment', $comments );
			$this->comment_count = count( $this->comments );

			$post_ids = array();

			foreach ( $this->comments as $comment ) {
				$post_ids[] = (int) $comment->comment_post_ID;
			}

			$post_ids = join( ',', $post_ids );
			$join     = '';
			if ( $post_ids ) {
				$where = "AND {$wpdb->posts}.ID IN ($post_ids) ";
			} else {
				$where = 'AND 0';
			}
		}

		$pieces = array( 'where', 'groupby', 'join', 'orderby', 'distinct', 'fields', 'limits' );

		/*
		 * Apply post-paging filters on where and join. Only plugins that
		 * manipulate paging queries should use these hooks.
		 */
		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * Specifically for manipulating paging queries.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where_paged', array( $where, &$this ) );

			/**
			 * Filters the GROUP BY clause of the query.
			 *
			 * @since 2.0.0
			 *
			 * @param string   $groupby The GROUP BY clause of the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$groupby = apply_filters_ref_array( 'posts_groupby', array( $groupby, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * Specifically for manipulating paging queries.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join_paged', array( $join, &$this ) );

			/**
			 * Filters the ORDER BY clause of the query.
			 *
			 * @since 1.5.1
			 *
			 * @param string   $orderby The ORDER BY clause of the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$orderby = apply_filters_ref_array( 'posts_orderby', array( $orderby, &$this ) );

			/**
			 * Filters the DISTINCT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $distinct The DISTINCT clause of the query.
			 * @param WP_Query $this     The WP_Query instance (passed by reference).
			 */
			$distinct = apply_filters_ref_array( 'posts_distinct', array( $distinct, &$this ) );

			/**
			 * Filters the LIMIT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $limits The LIMIT clause of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$limits = apply_filters_ref_array( 'post_limits', array( $limits, &$this ) );

			/**
			 * Filters the SELECT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $fields The SELECT clause of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$fields = apply_filters_ref_array( 'posts_fields', array( $fields, &$this ) );

			/**
			 * Filters all query clauses at once, for convenience.
			 *
			 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
			 * fields (SELECT), and LIMITS clauses.
			 *
			 * @since 3.1.0
			 *
			 * @param string[] $clauses Associative array of the clauses for the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$clauses = (array) apply_filters_ref_array( 'posts_clauses', array( compact( $pieces ), &$this ) );

			$where    = isset( $clauses['where'] ) ? $clauses['where'] : '';
			$groupby  = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';
			$join     = isset( $clauses['join'] ) ? $clauses['join'] : '';
			$orderby  = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
			$distinct = isset( $clauses['distinct'] ) ? $clauses['distinct'] : '';
			$fields   = isset( $clauses['fields'] ) ? $clauses['fields'] : '';
			$limits   = isset( $clauses['limits'] ) ? $clauses['limits'] : '';
		}

		/**
		 * Fires to announce the query's current selection parameters.
		 *
		 * For use by caching plugins.
		 *
		 * @since 2.3.0
		 *
		 * @param string $selection The assembled selection query.
		 */
		do_action( 'posts_selection', $where . $groupby . $orderby . $limits . $join );

		/*
		 * Filters again for the benefit of caching plugins.
		 * Regular plugins should use the hooks above.
		 */
		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where_request', array( $where, &$this ) );

			/**
			 * Filters the GROUP BY clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $groupby The GROUP BY clause of the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$groupby = apply_filters_ref_array( 'posts_groupby_request', array( $groupby, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join_request', array( $join, &$this ) );

			/**
			 * Filters the ORDER BY clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $orderby The ORDER BY clause of the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$orderby = apply_filters_ref_array( 'posts_orderby_request', array( $orderby, &$this ) );

			/**
			 * Filters the DISTINCT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $distinct The DISTINCT clause of the query.
			 * @param WP_Query $this     The WP_Query instance (passed by reference).
			 */
			$distinct = apply_filters_ref_array( 'posts_distinct_request', array( $distinct, &$this ) );

			/**
			 * Filters the SELECT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $fields The SELECT clause of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$fields = apply_filters_ref_array( 'posts_fields_request', array( $fields, &$this ) );

			/**
			 * Filters the LIMIT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $limits The LIMIT clause of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$limits = apply_filters_ref_array( 'post_limits_request', array( $limits, &$this ) );

			/**
			 * Filters all query clauses at once, for convenience.
			 *
			 * For use by caching plugins.
			 *
			 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
			 * fields (SELECT), and LIMITS clauses.
			 *
			 * @since 3.1.0
			 *
			 * @param string[] $pieces Associative array of the pieces of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$clauses = (array) apply_filters_ref_array( 'posts_clauses_request', array( compact( $pieces ), &$this ) );

			$where    = isset( $clauses['where'] ) ? $clauses['where'] : '';
			$groupby  = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';
			$join     = isset( $clauses['join'] ) ? $clauses['join'] : '';
			$orderby  = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
			$distinct = isset( $clauses['distinct'] ) ? $clauses['distinct'] : '';
			$fields   = isset( $clauses['fields'] ) ? $clauses['fields'] : '';
			$limits   = isset( $clauses['limits'] ) ? $clauses['limits'] : '';
		}

		if ( ! empty( $groupby ) ) {
			$groupby = 'GROUP BY ' . $groupby;
		}
		if ( ! empty( $orderby ) ) {
			$orderby = 'ORDER BY ' . $orderby;
		}

		$found_rows = '';
		if ( ! $q['no_found_rows'] && ! empty( $limits ) ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$old_request = "SELECT $found_rows $distinct $fields FROM {$wpdb->posts} $join WHERE 1=1 $where $groupby $orderby $limits";
		$this->request = $old_request;

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the completed SQL query before sending.
			 *
			 * @since 2.0.0
			 *
			 * @param string   $request The complete SQL query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$this->request = apply_filters_ref_array( 'posts_request', array( $this->request, &$this ) );
		}

		/**
		 * Filters the posts array before the query takes place.
		 *
		 * Return a non-null value to bypass WordPress's default post queries.
		 *
		 * Filtering functions that require pagination information are encouraged to set
		 * the `found_posts` and `max_num_pages` properties of the WP_Query object,
		 * passed to the filter by reference. If WP_Query does not perform a database
		 * query, it will not have enough information to generate these values itself.
		 *
		 * @since 4.6.0
		 *
		 * @param array|null $posts Return an array of post data to short-circuit WP's query,
		 *                          or null to allow WP to run its normal queries.
		 * @param WP_Query   $this  The WP_Query instance (passed by reference).
		 */
		$this->posts = apply_filters_ref_array( 'posts_pre_query', array( null, &$this ) );

		if ( 'ids' == $q['fields'] ) {
			if ( null === $this->posts ) {
				$this->posts = $wpdb->get_col( $this->request );
			}

			$this->posts      = array_map( 'intval', $this->posts );
			$this->post_count = count( $this->posts );
			$this->set_found_posts( $q, $limits );

			return $this->posts;
		}

		if ( 'id=>parent' == $q['fields'] ) {
			if ( null === $this->posts ) {
				$this->posts = $wpdb->get_results( $this->request );
			}

			$this->post_count = count( $this->posts );
			$this->set_found_posts( $q, $limits );

			$r = array();
			foreach ( $this->posts as $key => $post ) {
				$this->posts[ $key ]->ID          = (int) $post->ID;
				$this->posts[ $key ]->post_parent = (int) $post->post_parent;

				$r[ (int) $post->ID ] = (int) $post->post_parent;
			}

			return $r;
		}

		if ( null === $this->posts ) {
			$split_the_query = ( $old_request == $this->request && "{$wpdb->posts}.*" == $fields && ! empty( $limits ) && $q['posts_per_page'] < 500 );

			/**
			 * Filters whether to split the query.
			 *
			 * Splitting the query will cause it to fetch just the IDs of the found posts
			 * (and then individually fetch each post by ID), rather than fetching every
			 * complete row at once. One massive result vs. many small results.
			 *
			 * @since 3.4.0
			 *
			 * @param bool     $split_the_query Whether or not to split the query.
			 * @param WP_Query $this            The WP_Query instance.
			 */
			$split_the_query = apply_filters( 'split_the_query', $split_the_query, $this );

			if ( $split_the_query ) {
				// First get the IDs and then fill in the objects

				$this->request = "SELECT $found_rows $distinct {$wpdb->posts}.ID FROM {$wpdb->posts} $join WHERE 1=1 $where $groupby $orderby $limits";

				/**
				 * Filters the Post IDs SQL request before sending.
				 *
				 * @since 3.4.0
				 *
				 * @param string   $request The post ID request.
				 * @param WP_Query $this    The WP_Query instance.
				 */
				$this->request = apply_filters( 'posts_request_ids', $this->request, $this );

				$ids = $wpdb->get_col( $this->request );

				if ( $ids ) {
					$this->posts = $ids;
					$this->set_found_posts( $q, $limits );
					_prime_post_caches( $ids, $q['update_post_term_cache'], $q['update_post_meta_cache'] );
				} else {
					$this->posts = array();
				}
			} else {
				$this->posts = $wpdb->get_results( $this->request );
				$this->set_found_posts( $q, $limits );
			}
		}

		// Convert to WP_Post objects.
		if ( $this->posts ) {
			$this->posts = array_map( 'get_post', $this->posts );
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the raw post results array, prior to status checks.
			 *
			 * @since 2.3.0
			 *
			 * @param WP_Post[] $posts Array of post objects.
			 * @param WP_Query  $this  The WP_Query instance (passed by reference).
			 */
			$this->posts = apply_filters_ref_array( 'posts_results', array( $this->posts, &$this ) );
		}

		if ( ! empty( $this->posts ) && $this->is_comment_feed && $this->is_singular ) {
			/** This filter is documented in wp-includes/query.php */
			$cjoin = apply_filters_ref_array( 'comment_feed_join', array( '', &$this ) );

			/** This filter is documented in wp-includes/query.php */
			$cwhere = apply_filters_ref_array( 'comment_feed_where', array( "WHERE comment_post_ID = '{$this->posts[0]->ID}' AND comment_approved = '1'", &$this ) );

			/** This filter is documented in wp-includes/query.php */
			$cgroupby = apply_filters_ref_array( 'comment_feed_groupby', array( '', &$this ) );
			$cgroupby = ( ! empty( $cgroupby ) ) ? 'GROUP BY ' . $cgroupby : '';

			/** This filter is documented in wp-includes/query.php */
			$corderby = apply_filters_ref_array( 'comment_feed_orderby', array( 'comment_date_gmt DESC', &$this ) );
			$corderby = ( ! empty( $corderby ) ) ? 'ORDER BY ' . $corderby : '';

			/** This filter is documented in wp-includes/query.php */
			$climits = apply_filters_ref_array( 'comment_feed_limits', array( 'LIMIT ' . get_option( 'posts_per_rss' ), &$this ) );

			$comments_request = "SELECT {$wpdb->comments}.* FROM {$wpdb->comments} $cjoin $cwhere $cgroupby $corderby $climits";
			$comments         = $wpdb->get_results( $comments_request );
			// Convert to WP_Comment
			$this->comments      = array_map( 'get_comment', $comments );
			$this->comment_count = count( $this->comments );
		}

		// Check post status to determine if post should be displayed.
		if ( ! empty( $this->posts ) && ( $this->is_single || $this->is_page ) ) {
			$status = get_post_status( $this->posts[0] );
			if ( 'attachment' === $this->posts[0]->post_type && 0 === (int) $this->posts[0]->post_parent ) {
				$this->is_page       = false;
				$this->is_single     = true;
				$this->is_attachment = true;
			}
			$post_status_obj = get_post_status_object( $status );

			// If the post_status was specifically requested, let it pass through.
			if ( ! $post_status_obj->public && ! in_array( $status, $q_status ) ) {

				if ( ! is_user_logged_in() ) {
					// User must be logged in to view unpublished posts.
					$this->posts = array();
				} else {
					if ( $post_status_obj->protected ) {
						// User must have edit permissions on the draft to preview.
						if ( ! current_user_can( $edit_cap, $this->posts[0]->ID ) ) {
							$this->posts = array();
						} else {
							$this->is_preview = true;
							if ( 'future' != $status ) {
								$this->posts[0]->post_date = current_time( 'mysql' );
							}
						}
					} elseif ( $post_status_obj->private ) {
						if ( ! current_user_can( $read_cap, $this->posts[0]->ID ) ) {
							$this->posts = array();
						}
					} else {
						$this->posts = array();
					}
				}
			}

			if ( $this->is_preview && $this->posts && current_user_can( $edit_cap, $this->posts[0]->ID ) ) {
				/**
				 * Filters the single post for preview mode.
				 *
				 * @since 2.7.0
				 *
				 * @param WP_Post  $post_preview  The Post object.
				 * @param WP_Query $this          The WP_Query instance (passed by reference).
				 */
				$this->posts[0] = get_post( apply_filters_ref_array( 'the_preview', array( $this->posts[0], &$this ) ) );
			}
		}

		// Put sticky posts at the top of the posts array
		$sticky_posts = get_option( 'sticky_posts' );
		if ( $this->is_home && $page <= 1 && is_array( $sticky_posts ) && ! empty( $sticky_posts ) && ! $q['ignore_sticky_posts'] ) {
			$num_posts     = count( $this->posts );
			$sticky_offset = 0;
			// Loop over posts and relocate stickies to the front.
			for ( $i = 0; $i < $num_posts; $i++ ) {
				if ( in_array( $this->posts[ $i ]->ID, $sticky_posts ) ) {
					$sticky_post = $this->posts[ $i ];
					// Remove sticky from current position
					array_splice( $this->posts, $i, 1 );
					// Move to front, after other stickies
					array_splice( $this->posts, $sticky_offset, 0, array( $sticky_post ) );
					// Increment the sticky offset. The next sticky will be placed at this offset.
					$sticky_offset++;
					// Remove post from sticky posts array
					$offset = array_search( $sticky_post->ID, $sticky_posts );
					unset( $sticky_posts[ $offset ] );
				}
			}

			// If any posts have been excluded specifically, Ignore those that are sticky.
			if ( ! empty( $sticky_posts ) && ! empty( $q['post__not_in'] ) ) {
				$sticky_posts = array_diff( $sticky_posts, $q['post__not_in'] );
			}

			// Fetch sticky posts that weren't in the query results
			if ( ! empty( $sticky_posts ) ) {
				$stickies = get_posts(
					array(
						'post__in'    => $sticky_posts,
						'post_type'   => $post_type,
						'post_status' => 'publish',
						'nopaging'    => true,
					)
				);

				foreach ( $stickies as $sticky_post ) {
					array_splice( $this->posts, $sticky_offset, 0, array( $sticky_post ) );
					$sticky_offset++;
				}
			}
		}

		// If comments have been fetched as part of the query, make sure comment meta lazy-loading is set up.
		if ( ! empty( $this->comments ) ) {
			wp_queue_comments_for_comment_meta_lazyload( $this->comments );
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the array of retrieved posts after they've been fetched and
			 * internally processed.
			 *
			 * @since 1.5.0
			 *
			 * @param WP_Post[] $posts Array of post objects.
			 * @param WP_Query  $this The WP_Query instance (passed by reference).
			 */
			$this->posts = apply_filters_ref_array( 'the_posts', array( $this->posts, &$this ) );
		}

		// Ensure that any posts added/modified via one of the filters above are
		// of the type WP_Post and are filtered.
		if ( $this->posts ) {
			$this->post_count = count( $this->posts );

			$this->posts = array_map( 'get_post', $this->posts );

			if ( $q['cache_results'] ) {
				update_post_caches( $this->posts, $post_type, $q['update_post_term_cache'], $q['update_post_meta_cache'] );
			}

			$this->post = reset( $this->posts );
		} else {
			$this->post_count = 0;
			$this->posts      = array();
		}

		if ( $q['lazy_load_term_meta'] ) {
			wp_queue_posts_for_term_meta_lazyload( $this->posts );
		}

		return $this->posts;
	}
}
