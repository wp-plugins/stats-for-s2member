<?php
/**
 * Stats for S2Member users list table class.
 *
 */
class S4S2MemberUsersList extends WP_List_Table {
	var $items;
	var $_users_per_page = 0;
	
	public function __construct() {
		
	}
	function set_users_per_page( $perPage ){
		$this->_users_per_page = $perPage;
	}
	function prepare_items() {
		global $role, $usersearch;

		$usersearch = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';

		$role = isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '';

		if ($this->_users_per_page == 0){
			$per_page = ( $this->is_site_users ) ? 'site_users_network_per_page' : 'users_per_page';
			$users_per_page = $this->get_items_per_page( $per_page );
		}else{
			$users_per_page = $this->_users_per_page;
		}

		$paged = $this->get_pagenum();

		$args = array(
			'number' => $users_per_page,
			'offset' => ( $paged-1 ) * $users_per_page,
			'role' => $role,
			'search' => $usersearch,
			'fields' => 'all_with_meta'
		);

		if ( '' !== $args['search'] )
			$args['search'] = '*' . $args['search'] . '*';

		if ( $this->is_site_users )
			$args['blog_id'] = $this->site_id;

		if ( isset( $_REQUEST['orderby'] ) )
			$args['orderby'] = $_REQUEST['orderby'];

		if ( isset( $_REQUEST['order'] ) )
			$args['order'] = $_REQUEST['order'];
			
		

		// Query the user IDs for this page
		$wp_user_search = new WP_User_Query( $args );

		$this->items = $wp_user_search->get_results();
		

		$this->set_pagination_args( array(
			'total_items' => $wp_user_search->get_total(),
			'per_page' => $users_per_page,
		) );
	}
	function user_registration_query(&$query = FALSE){
		global $wpdb;
		
		if ( isset( $_REQUEST['registeration'] ) ){
			//$query->query_from = " FROM `" . $wpdb->users . "` INNER JOIN `" . $wpdb->usermeta . "` ON `" . $wpdb->users . "`.`ID` = `" . $wpdb->usermeta . "`.`user_id`";
			$query->query_where .= " AND UNIX_TIMESTAMP(`" . $wpdb->users . "`.`user_registered`) >=".$_REQUEST['registeration'];
			//$query->query_where .= " AND (`" . $wpdb->usermeta . "`.`meta_key` = '" . $wpdb->prefix . "s2member_subscr_id' AND `" . $wpdb->usermeta . "`.`meta_value` <> '')";
		}
		
		if ( isset( $_REQUEST['expiredstart'] ) && isset( $_REQUEST['expiredend'] ) ){
			$query->query_from = " FROM `" . $wpdb->users . "` INNER JOIN `" . $wpdb->usermeta . "` ON `" . $wpdb->users . "`.`ID` = `" . $wpdb->usermeta . "`.`user_id`";
			$query->query_where .= " AND `" . $wpdb->usermeta . "`.`meta_key` = '" . $wpdb->prefix . "s2member_auto_eot_time' ".
								   " AND `" . $wpdb->usermeta . "`.`meta_value` <= ".$_REQUEST['expiredend'] . 
								   " AND `" . $wpdb->usermeta . "`.`meta_value` >= ".$_REQUEST['expiredstart'];
		}
	}
	function get_columns() {
		$c = array(
			'cb'       => '<input type="checkbox" />',
			'username' => __( 'Username' ),
			'name'     => __( 'Name' ),
			'email'    => __( 'E-mail' ),
			'role'     => __( 'Role' ),
			'posts'    => __( 'Posts' )
		);

		if ( $this->is_site_users )
			unset( $c['posts'] );

		return $c;
	}

	function get_sortable_columns() {
		$c = array(
			'username' => 'login',
			'name'     => 'name',
			'email'    => 'email',
		);

		if ( $this->is_site_users )
			unset( $c['posts'] );

		return $c;
	}

	function display_rows() {
		$style = '';
		foreach ( $this->items as $userid => $user_object ) {
			$role = reset( $user_object->roles );

			if ( is_multisite() && empty( $role ) )
				continue;

			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
			echo "\n\t", $this->single_row( $user_object, $style, $role, isset( $post_counts ) ? $post_counts[ $userid ] : 0 );
		}
	}

	/**
	 * Generate HTML for a single row on the users.php admin panel.
	 *
	 * @since 2.1.0
	 *
	 * @param object $user_object
	 * @param string $style Optional. Attributes added to the TR element.  Must be sanitized.
	 * @param string $role Key for the $wp_roles array.
	 * @param int $numposts Optional. Post count to display for this user.  Defaults to zero, as in, a new user has made zero posts.
	 * @return string
	 */
	function single_row( $user_object, $style = '', $role = '', $numposts = 0 ) {
		global $wp_roles;

		if ( !( is_object( $user_object ) && is_a( $user_object, 'WP_User' ) ) )
			$user_object = new WP_User( (int) $user_object );
		$user_object->filter = 'display';
		$email = $user_object->user_email;

		if ( $this->is_site_users )
			$url = "site-users.php?id={$this->site_id}&amp;";
		else
			$url = 'users.php?';

		$checkbox = '';
		// Check if the user for this row is editable
		if ( current_user_can( 'list_users' ) ) {
			// Set up the user editing link
			// TODO: make profile/user-edit determination a separate function
			if ( get_current_user_id() == $user_object->ID ) {
				$edit_link = 'profile.php';
			} else {
				$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( stripslashes( $_SERVER['REQUEST_URI'] ) ), "user-edit.php?user_id=$user_object->ID" ) );
			}

			// Set up the hover actions for this user
			$actions = array();

			if ( current_user_can( 'edit_user',  $user_object->ID ) ) {
				$edit = "<strong><a href=\"$edit_link\">$user_object->user_login</a></strong><br />";
				$actions['edit'] = '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>';
			} else {
				$edit = "<strong>$user_object->user_login</strong><br />";
			}

			if ( !is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'delete_user', $user_object->ID ) )
				$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( "users.php?action=delete&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Delete' ) . "</a>";
			if ( is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'remove_user', $user_object->ID ) )
				$actions['remove'] = "<a class='submitdelete' href='" . wp_nonce_url( $url."action=remove&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Remove' ) . "</a>";
			$actions = apply_filters( 'user_row_actions', $actions, $user_object );
			
			//remove actions
			//$edit .= $this->row_actions( $actions );

			// Set up the checkbox ( because the user is editable, otherwise its empty )
			$checkbox = "<input type='checkbox' name='users[]' id='user_{$user_object->ID}' class='$role' value='{$user_object->ID}' />";

		} else {
			$edit = '<strong>' . $user_object->user_login . '</strong>';
		}
		$role_name = isset( $wp_roles->role_names[$role] ) ? translate_user_role( $wp_roles->role_names[$role] ) : __( 'None' );
		$avatar = get_avatar( $user_object->ID, 32 );

		$r = "<tr id='user-$user_object->ID'$style>";

		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class=\"$column_name column-$column_name\"";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			switch ( $column_name ) {
				case 'cb':
					$r .= "<th scope='row' class='check-column'>$checkbox</th>";
					break;
				case 'username':
					$r .= "<td $attributes>$avatar $edit</td>";
					break;
				case 'name':
					$r .= "<td $attributes>$user_object->first_name $user_object->last_name</td>";
					break;
				case 'email':
					$r .= "<td $attributes><a href='mailto:$email' title='" . esc_attr( sprintf( __( 'E-mail: %s' ), $email ) ) . "'>$email</a></td>";
					break;
				case 'role':
					$r .= "<td $attributes>$role_name</td>";
					break;
				case 'posts':
					$attributes = 'class="posts column-posts num"' . $style;
					$r .= "<td $attributes>";
					if ( $numposts > 0 ) {
						$r .= "<a href='edit.php?author=$user_object->ID' title='" . esc_attr__( 'View posts by this author' ) . "' class='edit'>";
						$r .= $numposts;
						$r .= '</a>';
					} else {
						$r .= 0;
					}
					$r .= "</td>";
					break;
				default:
					$r .= "<td $attributes>";
					$r .= apply_filters( 'manage_users_custom_column', '', $column_name, $user_object->ID );
					$r .= "</td>";
			}
		}
		$r .= '</tr>';

		return $r;
	}
	
	function display() {
		$this->display_tablenav( 'top' );
		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>
		
			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>
		
			<tbody id="the-list"<?php if ( $singular ) echo " class='list:$singular'"; ?>>
				<?php $this->display_rows(); ?>
			</tbody>
		</table>
	<?php
		$this->display_tablenav( 'bottom' );
	}
	
	function print_column_headers( $with_id = true ) {
		$screen = get_current_screen();

		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$current_url = remove_query_arg( 'paged', $current_url );

		if ( isset( $_GET['orderby'] ) )
			$current_orderby = $_GET['orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}
	function get_table_classes() {
		return array( 'widefat', 'fixed', $this->_args['plural'] );
	}
	
	function get_column_info() {

		$columns = array('username' => 'Username', 'name' => 'Name', 'email' => 'E-mail', 'role' => 'Role', 'posts' => 'Posts', 's2member_registration_time' => 'Registration Date', 's2member_subscr_id' => 'Paid Subscr. ID', 
    					 's2member_ccaps' => 'Custom Capabilities', 's2member_login_counter' => '# Of Logins');
		$hidden = array();

		$_sortable = apply_filters( "manage_{$screen->id}_sortable_columns", $this->get_sortable_columns() );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) )
				continue;

			$data = (array) $data;
			if ( !isset( $data[1] ) )
				$data[1] = false;

			$sortable[$id] = $data;
		}

		$_column_headers = array( $columns, $hidden, $sortable );

		return $_column_headers;
	}
}

?>
