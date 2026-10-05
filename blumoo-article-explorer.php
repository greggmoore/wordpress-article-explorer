<?php
/**
 * Plugin Name: Blumoo Article Explorer
 * Description: A progressively enhanced, accessible article search portfolio demo.
 * Version: 1.0.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: blumoo-article-explorer
 */

namespace Blumoo\ArticleExplorer;

defined( 'ABSPATH' ) || exit;

/** Only published, non-password-protected content is exposed publicly. */
function query_articles( $search = '', $category = 0, $page = 1 ) {
    return new \WP_Query( array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'has_password' => false,
        'posts_per_page' => 6,
        'paged' => $page,
        's' => $search,
        'cat' => $category,
        'ignore_sticky_posts' => true,
        'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ),
    ) );
}

/** Shared rendering keeps the initial HTML and REST results consistent. */
function render_cards( $posts ) {
    ob_start();
    foreach ( $posts as $post ) {
        ?>
        <li class="bae-card">
            <article>
                <h3><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
                <time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( get_the_date( '', $post ) ); ?></time>
                <p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_excerpt ?: $post->post_content ) ), 28 ) ); ?></p>
            </article>
        </li>
        <?php
    }
    return ob_get_clean();
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'blumoo-explorer/v1', '/articles', array(
        'methods' => \WP_REST_Server::READABLE,
        // Intentionally public: this route only reads public article summaries.
        'permission_callback' => '__return_true',
        'args' => array(
            'search' => array(
                'type' => 'string', 'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ( $value ) { return is_string( $value ) && strlen( $value ) <= 200; },
            ),
            'category' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
            'page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 1 ),
        ),
        'callback' => function ( \WP_REST_Request $request ) {
            $query = query_articles( $request['search'], (int) $request['category'], (int) $request['page'] );
            return rest_ensure_response( array(
                'html' => render_cards( $query->posts ),
                'total' => (int) $query->found_posts,
                'pages' => min( 100, (int) $query->max_num_pages ),
            ) );
        },
    ) );
} );

add_action( 'wp_enqueue_scripts', function () {
    // Small stylesheet loaded on all front-end pages so shortcodes rendered after
    // wp_head (including templates/widgets) still receive their styles.
    wp_enqueue_style( 'bae', plugins_url( 'assets/explorer.css', __FILE__ ), array(), '1.0.0' );
} );

add_shortcode( 'blumoo_article_explorer', function () {
    wp_enqueue_script( 'bae', plugins_url( 'assets/explorer.js', __FILE__ ), array(), '1.0.0', array( 'in_footer' => true, 'strategy' => 'defer' ) );
    $id = wp_unique_id( 'bae-' );
    $query = query_articles();
    $categories = get_categories( array( 'hide_empty' => true ) );
    ob_start();
    ?>
    <section class="bae" data-endpoint="<?php echo esc_url( rest_url( 'blumoo-explorer/v1/articles' ) ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>title">
        <h2 id="<?php echo esc_attr( $id ); ?>title"><?php esc_html_e( 'Explore articles', 'blumoo-article-explorer' ); ?></h2>
        <!-- Native WordPress search remains usable when JavaScript is unavailable. -->
        <form class="bae-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
            <div><label for="<?php echo esc_attr( $id ); ?>search"><?php esc_html_e( 'Search articles', 'blumoo-article-explorer' ); ?></label>
            <input id="<?php echo esc_attr( $id ); ?>search" name="s" type="search" maxlength="200"></div>
            <div><label for="<?php echo esc_attr( $id ); ?>category"><?php esc_html_e( 'Category', 'blumoo-article-explorer' ); ?></label>
            <select id="<?php echo esc_attr( $id ); ?>category" name="cat">
                <option value="0"><?php esc_html_e( 'All categories', 'blumoo-article-explorer' ); ?></option>
                <?php if ( ! is_wp_error( $categories ) ) : foreach ( $categories as $category ) : ?>
                    <option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
                <?php endforeach; endif; ?>
            </select></div>
            <input type="hidden" name="post_type" value="post">
            <button type="submit"><?php esc_html_e( 'Search', 'blumoo-article-explorer' ); ?></button>
        </form>
        <p class="bae-status" role="status" aria-live="polite" aria-atomic="true"><?php echo esc_html( sprintf( __( '%d articles found.', 'blumoo-article-explorer' ), $query->found_posts ) ); ?></p>
        <ul class="bae-results"><?php echo render_cards( $query->posts ); // Escaped in render_cards(). ?></ul>
        <div class="bae-pagination" hidden>
            <button type="button" data-direction="-1" disabled><?php esc_html_e( 'Previous', 'blumoo-article-explorer' ); ?></button>
            <span class="bae-page"></span>
            <button type="button" data-direction="1" <?php disabled( $query->max_num_pages <= 1 ); ?>><?php esc_html_e( 'Next', 'blumoo-article-explorer' ); ?></button>
        </div>
    </section>
    <?php
    return ob_get_clean();
} );
