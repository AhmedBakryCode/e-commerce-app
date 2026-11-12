<?php
add_action( 'wp_ajax_cosmetics_shop_run_demo_import', 'cosmetics_shop_run_demo_import' );

function cosmetics_shop_run_demo_import() {

    // Security
    check_ajax_referer( 'cosmetics_shop_demo_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    if ( get_option( 'cosmetics_shop_demo_import_completed' ) ) {
        wp_send_json_success( 'Already imported' );
    }

    if ( ! class_exists( 'WooCommerce' ) ) {
        wp_send_json_error( 'WooCommerce not active' );
    }

    /*  CREATE PRODUCT CATEGORIES */
    $categories = array( 'Slider Products', 'Shop Products' );
    $category_ids = array();

    foreach ( $categories as $category ) {
        $term = term_exists( $category, 'product_cat' );
        if ( ! $term ) {
            $term = wp_insert_term( $category, 'product_cat' );
        }
        $category_ids[ $category ] = (int) $term['term_id'];
    }

    /*  SLIDER PRODUCTS (Hero Section) */
    $slider_products = array(
        array(
            'title' => 'Hydra Luxe Cream',
            'price' => '120',
            'image' => get_stylesheet_directory() . '/images/slider-product1.png',
            'short_description' => 'Luxury hydration cream for radiant skin.',
            'description' => 'Hydra Luxe Cream delivers deep nourishment with a rich yet lightweight texture.
            Helps restore moisture balance and improve skin elasticity.
            Perfect for premium skincare routines.'
        ),
        array(
            'title' => 'Silk Touch Moisturizer',
            'price' => '95',
            'image' => get_stylesheet_directory() . '/images/slider-product2.png',
            'short_description' => 'Ultra-smooth moisturizer for daily hydration.',
            'description' => 'Silk Touch Moisturizer absorbs quickly to hydrate and soften skin.
            Keeps skin fresh and glowing all day long.
            Suitable for all skin types.'
        ),
        array(
            'title' => 'Glow Renewal Serum',
            'price' => '110',
            'image' => get_stylesheet_directory() . '/images/slider-product3.png',
            'short_description' => 'Revitalizing serum for dull skin.',
            'description' => 'Glow Renewal Serum improves skin tone and texture.
            Lightweight formula with fast absorption.
            Leaves skin visibly brighter and refreshed.'
        ),
    );

    /*  SHOP PRODUCTS (Best Selling Section) */
    $shop_products = array(
        array(
            'title' => 'Lumiskin Glow Serum',
            'price' => '31',
            'image' => get_stylesheet_directory() . '/images/slider-product1.png',
            'short_description' => 'Boosts natural radiance and hydration.',
            'description' => 'Lumiskin Glow Serum deeply nourishes the skin while enhancing its natural glow.
            Ideal for daily skincare routines.'
        ),
        array(
            'title' => 'Velvet Touch Foundation',
            'price' => '31',
            'image' => get_stylesheet_directory() . '/images/slider-product2.png',
            'short_description' => 'Smooth foundation with soft matte finish.',
            'description' => 'Velvet Touch Foundation offers buildable coverage with a lightweight feel.
            Perfect for a flawless everyday look.'
        ),
        array(
            'title' => 'Purebloom Face Cream',
            'price' => '31',
            'image' => get_stylesheet_directory() . '/images/slider-product3.png',
            'short_description' => 'Deep hydration for soft skin.',
            'description' => 'Purebloom Face Cream restores moisture and improves skin comfort.
            Gentle and suitable for daily use.'
        ),
        array(
            'title' => 'Radiance Revive Toner',
            'price' => '31',
            'image' => get_stylesheet_directory() . '/images/product4.png',
            'short_description' => 'Refreshing toner for glowing skin.',
            'description' => 'Radiance Revive Toner removes impurities and tightens pores.
            Prepares skin for serums and creams.'
        ),
    );

    /*  PRODUCT INSERT FUNCTION*  */
    function cosmetics_insert_product( $product, $category_id ) {

        $product_id = wp_insert_post( array(
            'post_title'   => $product['title'],
            'post_content' => $product['description'],
            'post_excerpt' => $product['short_description'],
            'post_status'  => 'publish',
            'post_type'    => 'product',
        ));

        if ( is_wp_error( $product_id ) ) {
            return;
        }

        update_post_meta( $product_id, '_regular_price', $product['price'] );
        update_post_meta( $product_id, '_price', $product['price'] );

        wp_set_object_terms( $product_id, $category_id, 'product_cat' );

        if ( ! empty( $product['image'] ) && file_exists( $product['image'] ) ) {
            $tmp = wp_tempnam( $product['image'] );
            copy( $product['image'], $tmp );

            $file = array(
                'name'     => basename( $product['image'] ),
                'tmp_name' => $tmp
            );

            $attachment_id = media_handle_sideload( $file, $product_id );

            if ( ! is_wp_error( $attachment_id ) ) {
                set_post_thumbnail( $product_id, $attachment_id );
            }
        }
    }

    /*  INSERT ALL PRODUCTS */
    foreach ( $slider_products as $product ) {
        cosmetics_insert_product( $product, $category_ids['Slider Products'] );
    }

    foreach ( $shop_products as $product ) {
        cosmetics_insert_product( $product, $category_ids['Shop Products'] );
    }

    update_option( 'cosmetics_shop_demo_import_completed', true );

    wp_send_json_success( 'Demo imported successfully' );
}
