<?php
/**
 * MainWP Child Plugin Pages
 *
 * Manage the MainWP Child plugin pages.
 *
 * @package MainWP\Child
 */

namespace MainWP\Child;

/**
 * Class MainWP_Pages
 *
 * Manage the MainWP Child plugin pages.
 */
class MainWP_Pages {

    /**
     * Public static variable to hold the single instance of the class.
     *
     * @var mixed Default null
     */
    protected static $instance = null;

    /**
     * Public static variable containing the sub-pages array.
     *
     * @var array Subpages array.
     */
    public static $subPages;

    /**
     * Public statis variable to determine whether or not MainWP Child Plugin subpages should be loaded. Default: false.
     *
     * @var bool true|false.
     */
    public static $subPagesLoaded = false;

    /**
     * Public statis variable to contain custom branding title.
     *
     * @var string Branding title.
     */
    public static $brandingTitle = null;

    /**
     * Method get_class_name()
     *
     * Get class name.
     *
     * @return string __CLASS__ Class name.
     */
    public static function get_class_name() {
        return __CLASS__;
    }

    /**
     * MainWP_Pages constructor.
     *
     * Run any time class is called.
     */
    public function __construct() {
    }

    /**
     * Method get_instance()
     *
     * Create a public static instance.
     *
     * @return mixed Class instance.
     */
    public static function get_instance() {
        if ( null === static::$instance ) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Initiate actions and filters.
     */
    public function init() {
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        add_action( 'admin_head', array( &$this, 'admin_head' ) );
        add_action( 'admin_notices', array( &$this, 'admin_notice' ) );
        add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
    }


    /**
     * Show disconnected admin notice.
     *
     * Show the Warning notice in case the site is not connected to MainWP Dashboard.
     *
     * @uses \MainWP\Child\MainWP_Child_Branding::get_branding_options()
     * @uses \MainWP\Child\MainWP_Child_Branding::is_branding()
     */
    public function admin_notice() {
        // Admin Notice...
        if (! get_option('mainwp_child_pubkey') && MainWP_Helper::is_admin() && is_admin()) {
            $branding_opts = MainWP_Child_Branding::instance()->get_branding_options();
            $child_name = ('' === $branding_opts['branding_preserve_title']) ? 'WPDash Child' : $branding_opts['branding_preserve_title'];
            $dashboard_name = ('' === $branding_opts['branding_preserve_title']) ? 'WPDash' : $branding_opts['branding_preserve_title'];

            $msg = '<div style="margin:50px 20px 20px 0;background:#fff;border:1px solid #c3c4c7;border-top-color:#d63638;border-top-width:5px;padding:20px;">';
            $msg .= '<h3 style="margin-top:0;color:#d63638;font-weight:900;">'.esc_html__('Attention! ',
                    'mainwp-child').$child_name.esc_html__(' plugin is activated but not connected.', 'mainwp-child').'</h3>';
            $msg .= '<p style="font-size:15px">'.esc_html__('Please add this site to your ',
                    'mainwp-child').$dashboard_name.' '.esc_html__('NOW or deactivate the ',
                    'mainwp-child').$child_name.esc_html__(' plugin until you are ready to connect this site to your Dashboard in order to avoid unexpected security issues. ',
                    'mainwp-child');
            if (! MainWP_Child_Branding::instance()->is_branding()) {
                $msg .= '<p style="font-size:15px">'.esc_html__('You can also turn on the unique security ID option in ',
                        'mainwp-child').$child_name.sprintf(esc_html__(' %1$ssettings%2$s if you would like extra security and additional time to add this site to your Dashboard. ',
                        'maiwnip-child'), '<a href="admin.php?page=wpdash_child_tab">', '</a>');
            }
            $msg .= '</div>';
            echo wp_kses_post( $msg );
        }
    }

    /**
     * Add and remove Admin Menu Items dependant upon Branding settings.
     *
     * @uses \MainWP\Child\MainWP_Child_Branding::get_branding_options()
     */
    public function admin_menu() { //phpcs:ignore -- NOSONAR - complex method.
        $branding_opts      = MainWP_Child_Branding::instance()->get_branding_options();
        $is_hide            = isset( $branding_opts['hide'] ) ? $branding_opts['hide'] : '';
        $cancelled_branding = $branding_opts['cancelled_branding'];
        $uri                = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        if ( isset( $branding_opts['remove_wp_tools'] ) && $branding_opts['remove_wp_tools'] && ! $cancelled_branding ) {
            remove_menu_page( 'tools.php' );
            $pos = $uri ? stripos( $uri, 'tools.php' ) || stripos( $uri, 'import.php' ) || stripos( $uri, 'export.php' ) : false;
            if ( false !== $pos ) {
                wp_safe_redirect( get_option( 'siteurl' ) . '/wp-admin/index.php' );
            }
        }
        // if preserve branding and do not remove menus.
        if ( isset( $branding_opts['remove_wp_setting'] ) && $branding_opts['remove_wp_setting'] && ! $cancelled_branding ) {
            remove_menu_page( 'options-general.php' );
            $pos = $uri ? ( stripos( $uri, 'options-general.php' ) || stripos( $uri, 'options-writing.php' ) || stripos( $uri, 'options-reading.php' ) || stripos( $uri, 'options-discussion.php' ) || stripos( $uri, 'options-media.php' ) || stripos( $uri, 'options-permalink.php' ) ) : false;
            if ( false !== $pos ) {
                wp_safe_redirect( get_option( 'siteurl' ) . '/wp-admin/index.php' );
                exit();
            }
        }

        if ( isset( $branding_opts['remove_permalink'] ) && $branding_opts['remove_permalink'] && ! $cancelled_branding ) {
            remove_submenu_page( 'options-general.php', 'options-permalink.php' );
            $pos = $uri ? stripos( $uri, 'options-permalink.php' ) : false;
            if ( false !== $pos ) {
                wp_safe_redirect( get_option( 'siteurl' ) . '/wp-admin/index.php' );
                exit();
            }
        }

        $remove_all_child_menu = false;
        if ( isset( $branding_opts['remove_setting'] ) && isset( $branding_opts['remove_restore'] ) && isset( $branding_opts['remove_server_info'] ) && $branding_opts['remove_setting'] && $branding_opts['remove_restore'] && $branding_opts['remove_server_info'] ) {
            $remove_all_child_menu = true;
        }

        // if preserve branding and do not hide menus.
        if ( ( ! $remove_all_child_menu && 'T' !== $is_hide ) || $cancelled_branding ) {

            $branding_header = isset( $branding_opts['branding_header'] ) ? $branding_opts['branding_header'] : array();
            if ( ( is_array( $branding_header ) && ! empty( $branding_header['name'] ) ) && ! $cancelled_branding ) {
                static::$brandingTitle = stripslashes( $branding_header['name'] );
                $child_menu_title      = stripslashes( $branding_header['name'] );
                $child_page_title      = $child_menu_title . ' Settings';
            } else {
                $child_menu_title = 'WPDash Child';
                $child_page_title = 'WPDash Child Settings';
            }
            $this->init_pages( $child_menu_title, $child_page_title );
        }
    }

    /**
     * Initiate MainWP Child Plugin pages.
     *
     * @param string $child_menu_title New MainWP Child Plugin title defined in branding settings.
     * @param string $child_page_title New MainWP Child Plugin page title defined in branding settings.
     *
     * @uses \MainWP\Child\MainWP_Clone_Page::get_class_name()
     */
    private function init_pages( $child_menu_title, $child_page_title ) { //phpcs:ignore -- NOSONAR - complex.

        $settingsPage = add_submenu_page('options-general.php', $child_page_title, $child_menu_title, 'manage_options', 'wpdash_child_tab',
            [&$this, 'render_pages']);

        add_action( 'admin_print_scripts-' . $settingsPage, array( MainWP_Clone_Page::get_class_name(), 'print_scripts' ) );

        $sub_pages = array();

        $all_subpages = apply_filters_deprecated( 'mainwp-child-init-subpages', array( array() ), '4.0.7.1', 'mainwp_child_init_subpages' ); // NOSONAR - no IP.
        $all_subpages = apply_filters( 'mainwp_child_init_subpages', $all_subpages );

        if ( ! is_array( $all_subpages ) ) {
            $all_subpages = array();
        }

        if ( ! static::$subPagesLoaded ) {
            foreach ( $all_subpages as $page ) {
                $slug = isset( $page['slug'] ) ? $page['slug'] : '';
                if ( empty( $slug ) ) {
                    continue;
                }
                $subpage          = array();
                $subpage['slug']  = $slug;
                $subpage['title'] = $page['title'];
                $subpage['page']  = 'mainwp-' . str_replace( ' ', '-', strtolower( str_replace( '-', ' ', $slug ) ) );
                if ( isset( $page['callback'] ) ) {
                    $subpage['callback'] = $page['callback'];
                    $created_page        = add_submenu_page( 'options-general.php', $subpage['title'], '<div class="mainwp-hidden">' . $subpage['title'] . '</div>', 'manage_options', $subpage['page'], $subpage['callback'] );
                    if ( isset( $page['load_callback'] ) ) {
                        $subpage['load_callback'] = $page['load_callback'];
                        add_action( 'load-' . $created_page, $subpage['load_callback'] );
                    }
                }
                $sub_pages[] = $subpage;
            }
            static::$subPages       = $sub_pages;
            static::$subPagesLoaded = true;
        }
        add_action( 'mainwp-child-pageheader', array( __CLASS__, 'render_header' ) );
        add_action( 'mainwp-child-pagefooter', array( __CLASS__, 'render_footer' ) );

        /**
         * WordPress submenu array.
         *
         * @global array $submenu WordPress submenu array.
         */
        global $submenu;

        if ( isset( $submenu['options-general.php'] ) ) {
            foreach ( $submenu['options-general.php'] as $index => $item ) {
                if ( 'mainwp-reports-page' === $item[2] || 'mainwp-reports-settings' === $item[2] ) {
                    unset( $submenu['options-general.php'][ $index ] );
                }
            }
        }
    }

    /**
     * MainWP Child Plugin meta data.
     *
     * @param array  $plugin_meta Plugin meta.
     * @param string $plugin_file Plugin file.
     *
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function plugin_row_meta( $plugin_meta, $plugin_file ) {

        /**
         * MainWP Child instance.
         *
         * @global object
         */
        global $mainWPChild;

        if ( $mainWPChild->plugin_slug !== $plugin_file ) {
            return $plugin_meta;
        }
        return apply_filters( 'mainwp_child_plugin_row_meta', $plugin_meta, $plugin_file, $mainWPChild->plugin_slug );
    }

    /**
     * Render MainWP Child Plugin pages.
     *
     * @uses \MainWP\Child\MainWP_Child_Branding::get_branding_options()
     * @uses \MainWP\Child\MainWP_Child_Server_Information::render_page()
     * @uses \MainWP\Child\MainWP_Child_Server_Information::render_connection_details()
     * @uses \MainWP\Child\MainWP_Clone_Page::render()
     * @uses \MainWP\Child\MainWP_Clone_Page::render_normal_restore()
     * @uses \MainWP\Child\MainWP_Clone_Page::render_restore()
     */
    public function render_pages() { // phpcs:ignore -- NOSONAR - Current complexity is the only way to achieve desired results, pull request solutions appreciated.
        $shownPage     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $branding_opts = MainWP_Child_Branding::instance()->get_branding_options();

        $hide_settings          = isset( $branding_opts['remove_setting'] ) && $branding_opts['remove_setting'] ? true : false;
        $hide_restore           = isset( $branding_opts['remove_restore'] ) && $branding_opts['remove_restore'] ? true : false;
        $hide_server_info       = isset( $branding_opts['remove_server_info'] ) && $branding_opts['remove_server_info'] ? true : false;
        $hide_connection_detail = isset( $branding_opts['remove_connection_detail'] ) && $branding_opts['remove_connection_detail'] ? true : false;

        if ( '' === $shownPage ) {
            if ( ! $hide_settings ) {
                    $shownPage = 'settings';
            } elseif ( ! $hide_restore ) {
                $shownPage = 'restore-clone';
            } elseif ( ! $hide_server_info ) {
                $shownPage = 'server-info';
            } elseif ( ! $hide_connection_detail ) {
                $shownPage = 'connection-detail';
            }
        }

        static::render_header($shownPage, false, $show_clones);

        if (is_null($show_clones)) {
            $show_clones = true;
        }

        ?>
        <?php if ( ! $hide_settings ) { ?>
            <div class="mainwp-child-setting-tab settings" <?php echo 'settings' !== $shownPage ? 'style="display:none"' : ''; ?>>
                <?php $this->render_settings(); ?>
            </div>
        <?php } ?>

        <?php
        if ( ! $hide_restore && $show_clones ) {
            $fsmethod = MainWP_Child_Server_Information_Base::get_file_system_method();
            if ( 'direct' === $fsmethod ) { // to fix error some case of file system method is not direct.
                ?>
            <div class="mainwp-child-setting-tab restore-clone" <?php echo 'restore-clone' !== $shownPage ? 'style="display:none"' : ''; ?>>
                <?php
                if ( isset( $_SESSION['file'] ) ) {
                    MainWP_Clone_Page::render_restore();
                } else {
                    $sitesToClone = get_option( 'mainwp_child_clone_sites' );
                    if ( 0 !== (int) $sitesToClone ) {
                        MainWP_Clone_Page::render();
                    } else {
                        MainWP_Clone_Page::render_normal_restore();
                    }
                }
                ?>
            </div>
            <?php } ?>
        <?php } ?>

        <?php if ( ! $hide_server_info ) { ?>
            <div class="mainwp-child-setting-tab server-info" <?php echo 'server-info' !== $shownPage ? 'style="display:none"' : ''; ?>>
                <?php MainWP_Child_Server_Information::render_page(); ?>
            </div>
        <?php } ?>

            <?php if ( ! $hide_connection_detail ) { ?>
            <div class="mainwp-child-setting-tab connection-detail" <?php echo 'connection-detail' !== $shownPage ? 'style="display:none"' : ''; ?>>
                    <?php MainWP_Child_Server_Information::render_connection_details(); ?>
            </div>
        <?php } ?>
        <?php
        static::render_footer();
    }

    /**
     * Render page header.
     *
     * @param string $shownPage Page shown.
     * @param bool   $subpage Whether or not a subpage. Default: true.
     * @param bool   $show_clone_funcs Whether or not to show clone tabs.
     *
     * @uses \MainWP\Child\MainWP_Child_Branding::get_branding_options()
     */
    public static function render_header( $shownPage, $subpage = true, &$show_clone_funcs = true ) { // phpcs:ignore -- NOSONAR - Current complexity is the only way to achieve desired results, pull request solutions appreciated.
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        if ( ! empty( $tab ) ) {
            $shownPage = $tab;
        }

        if ( empty( $shownPage ) ) {
            $shownPage = 'settings';
        }

        $branding_opts = MainWP_Child_Branding::instance()->get_branding_options();

        $hide_settings          = isset( $branding_opts['remove_setting'] ) && $branding_opts['remove_setting'] ? true : false;
        $hide_restore           = isset( $branding_opts['remove_restore'] ) && $branding_opts['remove_restore'] ? true : false;
        $hide_server_info       = isset( $branding_opts['remove_server_info'] ) && $branding_opts['remove_server_info'] ? true : false;
        $hide_connection_detail = isset( $branding_opts['remove_connection_detail'] ) && $branding_opts['remove_connection_detail'] ? true : false;

        $sitesToClone = get_option( 'mainwp_child_clone_sites' );

        // put here to support hooks to show header.
        $is_connected_admin = false;
        $connected          = ! empty( get_option( 'mainwp_child_pubkey' ) ) ? true : false;
        if ( $connected ) {
            $current_user = wp_get_current_user();
            if ( $current_user ) {
                $is_connected_admin = get_option( 'mainwp_child_connected_admin' ) === $current_user->user_login ? true : false;
            }
        }
        $show_clone_funcs = $connected && $is_connected_admin ? true : false;

        ?>
        <style type="text/css">
            .mainwp-tabs
            {
                margin-top: 2em;
                border-bottom: 1px solid #e5e5e5;
            }

            #mainwp-tabs {
                clear: both ;
            }
            #mainwp-tabs .nav-tab-active {
                background: #fafafa;
                border-top: 1px solid #7fb100 !important;
                border-left: 1px solid #e5e5e5;
                border-right: 1px solid #e5e5e5;
                border-bottom: 1px solid #fafafa !important;
                color: #7fb100;
            }

            #mainwp-tabs .nav-tab {
                border-top: 1px solid #e5e5e5;
                border-left: 1px solid #e5e5e5;
                border-right: 1px solid #e5e5e5;
                border-bottom: 1px solid #e5e5e5;
                padding: 10px 16px;
                font-size: 14px;
                text-transform: uppercase;
            }

            #mainwp_wrap-inside {
                min-height: 80vh;
                height: 100%;
                margin-top: 0em;
                padding: 10px;
                background: #fafafa;
                border-top: none;
                border-bottom: 1px solid #e5e5e5;
                border-left: 1px solid #e5e5e5;
                border-right: 1px solid #e5e5e5;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                position: relative;
            }

            #mainwp_wrap-inside h2.hndle {
                font-size: 14px;
                padding: 8px 12px;
                margin: 0;
                line-height: 1.4;
            }

            .mainwp-hidden {
                display: none;
            }
        </style>

        <div class="wrap">
        <h2><i class="fa fa-file"></i> <?php echo esc_html(null === static::$brandingTitle ? 'WPDash Child' : static::$brandingTitle); ?></h2>
        <div style="clear: both;"></div><br/>
        <div class="mainwp-tabs" id="mainwp-tabs">
            <?php if (! $hide_settings) { ?>
                <a class="nav-tab pos-nav-tab
                <?php
                if ('settings' === $shownPage) {
                    echo 'nav-tab-active';
                }
                ?>
" tab-slug="settings" href="<?php echo $subpage ? 'options-general.php?page=wpdash_child_tab&tab=settings' : '#'; ?>"
                   style="margin-left: 0 !important;"><?php esc_html_e('Settings', 'mainwp-child'); ?></a>
            <?php } ?>
            <?php if (! $hide_restore && $show_clone_funcs) { ?>
                <a class="nav-tab pos-nav-tab
                <?php
                if ('restore-clone' === $shownPage) {
                    echo 'nav-tab-active';
                }
                ?>
" tab-slug="restore-clone"
                   href="<?php echo esc_url($subpage ? 'options-general.php?page=wpdash_child_tab&tab=restore-clone' : '#'); ?>"><?php echo esc_html__(0 !== (int) $sitesToClone ? 'Restore / Clone' : 'Restore',
                        'mainwp-child'); ?></a>
            <?php } ?>
            <?php if (! $hide_server_info) { ?>
                <a class="nav-tab pos-nav-tab
                <?php
                if ('server-info' === $shownPage) {
                    echo 'nav-tab-active';
                }
                ?>
" tab-slug="server-info"
                   href="<?php echo $subpage ? 'options-general.php?page=wpdash_child_tab&tab=server-info' : '#'; ?>"><?php esc_html_e('Server information',
                        'mainwp-child'); ?></a>
            <?php } ?>
            <?php if (! $hide_connection_detail) { ?>
                <a class="nav-tab pos-nav-tab
                            <?php
                if ('connection-detail' === $shownPage) {
                    echo 'nav-tab-active';
                }
                ?>
" tab-slug="connection-detail"
                   href="<?php echo $subpage ? 'options-general.php?page=wpdash_child_tab&tab=connection-detail' : '#'; ?>"><?php esc_html_e('Connection Details',
                        'mainwp-child'); ?></a>
            <?php } ?>
            <?php
            if (isset(static::$subPages) && is_array(static::$subPages)) {
                foreach (static::$subPages as $subPage) {
                    ?>
                    <a class="nav-tab pos-nav-tab
                    <?php
                    if ($shownPage === $subPage['slug']) {
                        echo 'nav-tab-active';
                    }
                    ?>
" tab-slug="<?php echo esc_attr($subPage['slug']); ?>"
                       href="options-general.php?page=<?php echo esc_html(rawurlencode($subPage['page'])); ?>"><?php echo esc_html($subPage['title']); ?></a>
                    <?php
                }
            }
            ?>
            <div style="clear:both;"></div>
        </div>
        <div style="clear:both;"></div>
        <script type="text/javascript">
            jQuery( document ).ready( function () {
                $hideMenu = jQuery( '#menu-settings li a .mainwp-hidden' );
                $hideMenu.each( function() {
                    jQuery( this ).closest( 'li' ).hide();
                } );

                var $tabs = jQuery( '.mainwp-tabs' );

                $tabs.on( 'click', 'a', function () {
                    if ( jQuery( this ).attr( 'href' ) !=='#' )
                        return true;

                    jQuery( '.mainwp-tabs > a' ).removeClass( 'nav-tab-active' );
                    jQuery( this ).addClass( 'nav-tab-active' );
                    jQuery( '.mainwp-child-setting-tab' ).hide();
                    var _tab = jQuery( this ).attr( 'tab-slug' );
                    jQuery( '.mainwp-child-setting-tab.' + _tab ).show();
                    return false;
                } );
            } );
        </script>

        <div id="mainwp_wrap-inside">

        <?php
    }

    /**
     * Render page footer.
     */
    public static function render_footer() {
        ?>
        </div>
        </div>
        <?php
    }

    /**
     * Render admin header.
     */
    public function admin_head() {
        if ( isset( $_GET['page'] ) && 'mainwp_child_tab' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
            ?>
            <style type="text/css">
                .mainwp-postbox-actions-top {
                    padding: 10px;
                    clear: both;
                    border-bottom: 1px solid #ddd;
                    background: #f5f5f5;
                }
                h3.mainwp_box_title {
                    font-family: "Open Sans",sans-serif;
                    font-size: 14px;
                    font-weight: 600;
                    line-height: 1.4;
                    margin: 0;
                    padding: 8px 12px;
                    border-bottom: 1px solid #eee;
                }
                .mainwp-child-setting-tab.connection-detail .postbox .inside{
                    margin: 0;
                    padding: 0;
                }
            </style>
            <?php
        }
    }

    /**
     * Render connection settings sub page.
     *
     * Apiki: This page is used to display the unique security ID and SSH key.
     *
     * @uses \MainWP\Child\MainWP_Helper::update_option()
     * @uses \MainWP\Child\MainWP_Repository::decodeKey()
     */
    public function render_settings()
    {

        $uniqueId = MainWP_Helper::get_site_unique_id();

        // phpcs:disable WordPress.Security.NonceVerification
        if (isset($_POST['submit']) && isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'child-settings')) {
            if (! $uniqueId) {
                MainWP_Helper::update_option('mainwp_child_uniqueId', MainWP_Helper::rand_string(12));
                echo '<script>location.reload();</script>';
            }
        }

        if (isset($_REQUEST['function'], $_REQUEST['page']) && $_REQUEST['page'] === 'wpdash_child_tab' && $_REQUEST['function'] === 'generate_manual') {
            (new MainWP_Repository())->generateManual();
        }

        $pubhKey = get_option('mainwp_child_pubkey');
        $sshKey = get_option('mainwp_child_sshkey');
        $sshTime = get_option('mainwp_child_sshtime');
        // phpcs:enable

        ?>
        <div class="postbox">
            <form method="post" action="options-general.php?page=wpdash_child_tab">
                <h2 class="hndle"><span><?php esc_html_e('Connection settings', 'mainwp-child'); ?></span></h2>
                <div class="inside">
                    <label for="repositoryKey"
                           style="margin-bottom5px; font-size: 13px; color: #000; font-weight: bold;"><?php esc_html_e('Security ID: ',
                            'mainwp-child'); ?>
                    </label>
                    <div class="howto"><?php esc_html_e('The unique security ID adds additional protection between the child plugin and your Dashboard. The unique security ID will need to match when being added to the Dashboard.',
                            'mainwp-child'); ?></div>
                    <div style="">
                        <?php if (! empty($uniqueId)) : ?>
                            <div style="border: 1px dashed #e5e5e5; background: #1c1d1b; font-size: 14px; font-weight: bold; padding: 1em 1em; margin: 1em; overflow-wrap: break-word; color: #fed502;">
                                <?php echo $uniqueId; ?>
                            </div>
                            <span style="margin-bottom: 5px; font-size: 13px; color: #ff2222; font-weight: bold;">
										    <?php echo esc_html__('Copy and add security ID in Dashboard to enable synchronization.',
                                                'mainwp-child'); ?>
                                        </span>

                        <?php else : ?>
                            <span style="color: #000; font-weight: bold;"><?php echo esc_html__('Your unique security ID is not configured, click the button to generate a new security ID.',
                                    'mainwp-child'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <hr style="color: #eee; padding: 0 5px 0 5px;">
                <h2 class="hndle"><span><?php esc_html_e('Repository Setup',
                            'mainwp-child'); ?>
                    </span>
                </h2>

                <div class="inside">
                    <div>
                        <div style="margin-top: 5px;">
                            <label for="repositoryKey"
                                   style="margin-bottom5px; font-size: 13px; color: #000; font-weight: bold;"><?php esc_html_e('SSH Key: ',
                                    'mainwp-child'); ?>
                            </label>
                            <div style="">
                                <?php if ($sshKey): ?>
                                    <span style="margin-bottom: 5px; font-size: 13px; color: #000">
										    <?php echo esc_html__('Copy and add ssh key in repository to enable secure authentication and synchronization.',
                                                'mainwp-child'); ?>
                                        </span>
                                    <div style="border: 1px dashed #e5e5e5; background: #1c1d1b; font-size: 14px; font-weight: bold; padding: 1em 1em; margin: 1em; overflow-wrap: break-word; color: #fed502;">
                                        <?php echo MainWP_Repository::decodeKey($sshKey); ?>
                                    </div>
                                    <div style="text-align: right; margin-bottom: 5px; margin-right: 1.5em; font-size: 11px; color: #000; font-weight: bold;">
                                        <?php esc_attr_e('Last sync: ', 'mainwp-child'); ?><?php echo get_gmt_from_date($sshTime, 'd/m/Y H:i:s'); ?>
                                    </div>

                                <?php else : ?>
                                    <span style="color: #000; font-weight: bold;"><?php esc_html__('Your security key is not configured, sync your site with WPDash, and try again',
                                            'mainwp-child'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                    <div class="howto" style="color: #ff2222; font-weight: bold;">
                        <?php esc_html_e('The repository is already configured, deactivate the plug and activate it again to configure.',
                            'mainwp-child'); ?></div>
                    <p class="submit" style="margin-top: 2.5em;">
                        <input type="submit" name="submit" id="submit" class="button button-primary button-hero"
                               value="<?php esc_attr_e('Save changes', 'mainwp-child'); ?>">
                        <?php if ($pubhKey && $uniqueId) : ?>
                            <a href="<?php \get_admin_url(); ?>options-general.php?page=wpdash_child_tab&function=generate_manual"
                               class="button button-secondary button-hero"> Update SSH</a>
                        <?php endif; ?>
                    </p>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('child-settings')); ?>">
                </div>
            </form>
        </div>

        <?php
    }
}
