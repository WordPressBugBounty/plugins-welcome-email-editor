<?php
/**
 * Email logs screens.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Logs;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * The email logs list and detail screens.
 */
class Logs_Output {

	/**
	 * Set up the hooks.
	 */
	public function setup() {

		add_filter( 'manage_weed_email_logs_posts_columns', array( $this, 'set_custom_email_logs_columns' ) );
		add_action( 'manage_weed_email_logs_posts_custom_column', array( $this, 'custom_email_logs_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-weed_email_logs', array( $this, 'bulk_actions' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_messages' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'filter_email_logs_by_status' ) );
		add_action( 'pre_get_posts', array( $this, 'modify_email_logs_query' ) );
		add_action( 'add_meta_boxes_weed_email_logs', array( $this, 'add_email_logs_metabox' ) );
		add_filter( 'get_search_query', array( $this, 'show_search_result_text' ) );
		add_filter( 'views_edit-weed_email_logs', array( $this, 'overview' ) );

	}

	/**
	 * Count the sent and failed emails of the last 7 and 30 days.
	 *
	 * @return array Counts per period (7, 30), each with 'Success' and 'Failed'.
	 */
	public function get_overview_counts() {

		global $wpdb;

		// Log dates are stored in the site's time zone.
		$now = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Compared with post_date, which is local time.

		$counts = array();

		foreach ( array( 7, 30 ) as $days ) {
			$counts[ $days ] = array(
				'Success' => 0,
				'Failed'  => 0,
			);
		}

		// One query for both periods.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Counts change with every email.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS status,
					SUM( p.post_date >= %s ) AS last_7,
					COUNT(*) AS last_30
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'status'
				WHERE p.post_type = 'weed_email_logs'
					AND p.post_status = 'publish'
					AND p.post_date >= %s
				GROUP BY pm.meta_value",
				gmdate( 'Y-m-d H:i:s', $now - 7 * DAY_IN_SECONDS ),
				gmdate( 'Y-m-d H:i:s', $now - 30 * DAY_IN_SECONDS )
			)
		);

		foreach ( $rows as $row ) {
			if ( isset( $counts[7][ $row->status ] ) ) {
				$counts[7][ $row->status ]  = (int) $row->last_7;
				$counts[30][ $row->status ] = (int) $row->last_30;
			}
		}

		return $counts;

	}

	/**
	 * Sent vs. failed emails, above the email logs list.
	 *
	 * Printed while WordPress outputs the list's views (All | Published), so it sits right above the list, below any notices.
	 *
	 * @param array $views The list views.
	 *
	 * @return array
	 */
	public function overview( $views ) {

		$counts  = $this->get_overview_counts();
		$periods = array(
			7  => __( 'Last 7 days', 'welcome-email-editor' ),
			30 => __( 'Last 30 days', 'welcome-email-editor' ),
		);
		$failed  = admin_url( 'edit.php?post_type=weed_email_logs&email_status=Failed' );

		?>
		<div class="weed-logs-overview">
			<?php
			foreach ( $periods as $days => $label ) :
				$sent_count   = $counts[ $days ]['Success'];
				$failed_count = $counts[ $days ]['Failed'];
				$total        = $sent_count + $failed_count;
				$sent_share   = $total ? round( $sent_count / $total * 100 ) : 0;

				// Show 99% instead of rounding up to 100% when something failed.
				if ( $failed_count && 100 === (int) $sent_share ) {
					$sent_share = 99;
				}

				// Likewise 1% instead of 0% when something was sent.
				if ( $sent_count && 0 === (int) $sent_share ) {
					$sent_share = 1;
				}
				?>
				<div class="weed-logs-overview-period">
					<div class="weed-logs-overview-head">
						<h2><?php echo esc_html( $label ); ?></h2>
						<?php if ( $total ) : ?>
							<span class="weed-logs-overview-rate<?php echo $failed_count ? ' has-failed' : ''; ?>">
								<?php
								/* translators: %s: Percentage of emails sent successfully. */
								echo esc_html( sprintf( __( '%s%% delivered', 'welcome-email-editor' ), number_format_i18n( $sent_share ) ) );
								?>
							</span>
						<?php endif; ?>
					</div>

					<div class="weed-logs-overview-bar" aria-hidden="true">
						<?php if ( $total ) : ?>
							<?php // %F: always a dot, no exponent, whatever the server's locale. ?>
							<span class="is-success" style="width: <?php echo esc_attr( sprintf( '%.2F', $sent_count / $total * 100 ) ); ?>%;"></span>
							<span class="is-error" style="width: <?php echo esc_attr( sprintf( '%.2F', $failed_count / $total * 100 ) ); ?>%;"></span>
						<?php endif; ?>
					</div>

					<?php if ( $total ) : ?>
						<p class="weed-logs-overview-legend">
							<span class="is-success">
								<?php
								/* translators: %s: Number of emails. */
								echo esc_html( sprintf( _n( '%s sent', '%s sent', $sent_count, 'welcome-email-editor' ), number_format_i18n( $sent_count ) ) );
								?>
							</span>
							<?php
							/* translators: %s: Number of emails. */
							$failed_label = sprintf( _n( '%s failed', '%s failed', $failed_count, 'welcome-email-editor' ), number_format_i18n( $failed_count ) );
							?>
							<?php if ( $failed_count ) : ?>
								<a href="<?php echo esc_url( $failed ); ?>" class="is-error"><?php echo esc_html( $failed_label ); ?></a>
							<?php else : ?>
								<span class="is-none"><?php echo esc_html( $failed_label ); ?></span>
							<?php endif; ?>
						</p>
					<?php else : ?>
						<p class="weed-logs-overview-legend"><?php esc_html_e( 'No emails yet.', 'welcome-email-editor' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php

		return $views;

	}

	/**
	 * Columns of the email logs list.
	 *
	 * @param array $columns The columns.
	 *
	 * @return array
	 */
	public function set_custom_email_logs_columns( $columns ) {

		return array(
			'cb'        => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'     => __( 'Subject', 'welcome-email-editor' ),
			'sender'    => __( 'Sender', 'welcome-email-editor' ),
			'recipient' => __( 'Recipient', 'welcome-email-editor' ),
			'status'    => __( 'Status', 'welcome-email-editor' ),
			'date'      => __( 'Date', 'welcome-email-editor' ),
		);

	}

	/**
	 * Values of the custom columns.
	 *
	 * @param string $column  The column.
	 * @param int    $post_id The log ID.
	 */
	public function custom_email_logs_column( $column, $post_id ) {

		switch ( $column ) {
			case 'sender':
			case 'recipient':
				echo esc_html( get_post_meta( $post_id, $column, true ) );
				break;

			case 'status':
				$this->status_badge( get_post_meta( $post_id, 'status', true ) );
				break;
		}

	}

	/**
	 * Output a status badge.
	 *
	 * @param string $status "Success" or "Failed".
	 */
	private function status_badge( $status ) {

		$labels = array(
			'Success' => __( 'Success', 'welcome-email-editor' ),
			'Failed'  => __( 'Failed', 'welcome-email-editor' ),
		);

		$class = 'Success' === $status ? 'is-success' : 'is-error';
		$label = isset( $labels[ $status ] ) ? $labels[ $status ] : $status;

		echo '<span class="weed-status-badge ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';

	}

	/**
	 * Row actions: "View" and "Delete Permanently"/"Trash" (no edit or quick edit).
	 *
	 * @param array    $actions The row actions.
	 * @param \WP_Post $post    The log.
	 *
	 * @return array
	 */
	public function row_actions( $actions, $post ) {

		if ( 'weed_email_logs' !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['inline hide-if-no-js'] );

		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html__( 'View', 'welcome-email-editor' ) . '</a>';
		}

		return $actions;

	}

	/**
	 * Bulk actions: no bulk edit, logs are read-only.
	 *
	 * @param array $actions The bulk actions.
	 *
	 * @return array
	 */
	public function bulk_actions( $actions ) {

		unset( $actions['edit'] );

		return $actions;

	}

	/**
	 * "1 email log permanently deleted." instead of WordPress' "1 post …".
	 *
	 * @param array $messages    The messages per post type.
	 * @param array $bulk_counts The number of affected posts per action.
	 *
	 * @return array
	 */
	public function bulk_messages( $messages, $bulk_counts ) {

		$messages['weed_email_logs'] = array(
			/* translators: %s: Number of email logs. */
			'deleted'   => _n( '%s email log permanently deleted.', '%s email logs permanently deleted.', $bulk_counts['deleted'], 'welcome-email-editor' ),
			/* translators: %s: Number of email logs. */
			'trashed'   => _n( '%s email log moved to the Trash.', '%s email logs moved to the Trash.', $bulk_counts['trashed'], 'welcome-email-editor' ),
			/* translators: %s: Number of email logs. */
			'untrashed' => _n( '%s email log restored from the Trash.', '%s email logs restored from the Trash.', $bulk_counts['untrashed'], 'welcome-email-editor' ),
		);

		return $messages;

	}

	/**
	 * The status filter above the list.
	 *
	 * @param string $post_type The post type.
	 */
	public function filter_email_logs_by_status( $post_type ) {

		if ( 'weed_email_logs' !== $post_type ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List filter.
		$selected = isset( $_GET['email_status'] ) ? sanitize_text_field( wp_unslash( $_GET['email_status'] ) ) : '';
		?>
		<label for="email_status" class="screen-reader-text"><?php esc_html_e( 'Filter by status', 'welcome-email-editor' ); ?></label>
		<select name="email_status" id="email_status">
			<option value=""><?php esc_html_e( 'All Statuses', 'welcome-email-editor' ); ?></option>
			<option value="Success" <?php selected( $selected, 'Success' ); ?>><?php esc_html_e( 'Success', 'welcome-email-editor' ); ?></option>
			<option value="Failed" <?php selected( $selected, 'Failed' ); ?>><?php esc_html_e( 'Failed', 'welcome-email-editor' ); ?></option>
		</select>
		<?php

	}

	/**
	 * Replace the edit screen's boxes with the log details.
	 */
	public function add_email_logs_metabox() {

		remove_meta_box( 'submitdiv', 'weed_email_logs', 'side' );
		remove_meta_box( 'slugdiv', 'weed_email_logs', 'normal' );

		add_meta_box(
			'email_logs_metabox',
			__( 'Email Log Details', 'welcome-email-editor' ),
			array( $this, 'email_logs_metabox_callback' ),
			'weed_email_logs',
			'normal',
			'high'
		);

	}

	/**
	 * The log details.
	 *
	 * @param \WP_Post $post The log.
	 */
	public function email_logs_metabox_callback( $post ) {

		$status          = get_post_meta( $post->ID, 'status', true );
		$server_response = get_post_meta( $post->ID, 'server_response', true );
		$details         = array(
			__( 'Subject', 'welcome-email-editor' )   => get_post_meta( $post->ID, 'subject', true ),
			__( 'Sender', 'welcome-email-editor' )    => get_post_meta( $post->ID, 'sender', true ),
			__( 'Recipient', 'welcome-email-editor' ) => get_post_meta( $post->ID, 'recipient', true ),
			__( 'Date', 'welcome-email-editor' )      => get_the_date( '', $post ) . ' ' . get_the_time( '', $post ),
		);
		?>
		<table class="form-table weed-email-log-details" role="presentation">
			<?php foreach ( $details as $label => $value ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td><?php echo esc_html( $value ); ?></td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'welcome-email-editor' ); ?></th>
				<td><?php $this->status_badge( $status ); ?></td>
			</tr>
			<?php if ( ! empty( $server_response ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Server Response', 'welcome-email-editor' ); ?></th>
					<td><?php echo esc_html( $server_response ); ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Message', 'welcome-email-editor' ); ?></th>
				<td>
					<?php if ( preg_match( '/<[a-z][^>]*>/i', $post->post_content ) ) : ?>
						<?php // HTML emails are shown in a sandboxed frame: no scripts, no access to the admin. ?>
						<iframe class="weed-email-log-message" sandbox="" srcdoc="<?php echo esc_attr( $post->post_content ); ?>" title="<?php esc_attr_e( 'Message', 'welcome-email-editor' ); ?>"></iframe>
					<?php else : ?>
						<div class="weed-email-log-message is-text"><?php echo esc_html( $post->post_content ); ?></div>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<p>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=weed_email_logs' ) ); ?>" class="button">&larr; <?php esc_html_e( 'Back to Email Logs', 'welcome-email-editor' ); ?></a>
			<?php if ( current_user_can( 'delete_log', $post->ID ) ) : // phpcs:ignore WordPress.WP.Capabilities.Unknown -- The log post type's capability. ?>
				<a href="<?php echo esc_url( get_delete_post_link( $post->ID, '', true ) ); ?>" class="button weed-delete-log"><?php esc_html_e( 'Delete Permanently', 'welcome-email-editor' ); ?></a>
			<?php endif; ?>
		</p>
		<?php

	}

	/**
	 * Search by recipient & subject, filter by status.
	 *
	 * @param \WP_Query $query The query.
	 */
	public function modify_email_logs_query( $query ) {

		global $pagenow;

		if ( ! is_admin() || ! $query->is_main_query() || 'weed_email_logs' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = array( 'relation' => 'AND' );

		if ( $query->is_search() ) {
			$search_term  = $query->get( 's' );
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'recipient',
					'value'   => $search_term,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'subject',
					'value'   => $search_term,
					'compare' => 'LIKE',
				),
			);

			$query->set( 's', '' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List filter.
		if ( 'edit.php' === $pagenow && ! empty( $_GET['email_status'] ) ) {
			$meta_query[] = array(
				'key'     => 'status',
				'value'   => sanitize_text_field( wp_unslash( $_GET['email_status'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List filter.
				'compare' => '=',
			);
		}

		if ( count( $meta_query ) > 1 ) {
			$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin list only.
		}

	}

	/**
	 * Keep the search term in the search box (the query's own term was cleared above).
	 *
	 * @param string $search_query The search query.
	 *
	 * @return string
	 */
	public function show_search_result_text( $search_query ) {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only.
		if ( is_admin() && isset( $_GET['post_type'], $_GET['s'] ) && 'weed_email_logs' === $_GET['post_type'] ) {
			return sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}
		// phpcs:enable

		return $search_query;

	}

}
