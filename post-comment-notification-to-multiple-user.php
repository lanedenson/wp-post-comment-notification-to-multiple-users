<?php
/*
Plugin Name: Post Comment Notification To Multiple Users
Plugin URI: http://wordpress.org/plugins/post-comment-notification-to-multiple-user/
Description: Send an email to multiple addresses when a new comment has been submitted.
Version: 1.1
Author: Rahul Mukherjee; modified by Lane Denson
Author URI: https://www.facebook.com/rahulmukherjee85/
License: Unknown
*/ 

$action = (!empty($_REQUEST['action'])) ? $_REQUEST['action'] : 'showForm';
$email_address = (!empty($_REQUEST['email_address'])) ? $_REQUEST['email_address'] : '';
$moderated_only = (!empty($_REQUEST['moderated_only'])) ? $_REQUEST['moderated_only'] : 0;
$cn_recipients = get_option("cn_recipients");
$cn_recipients_array = explode(",", $cn_recipients);

$moderated_only_option = get_option("cn_moderated_only");

if (empty($moderated_only_option)) {
	$moderated_only_option = 0;
}

if (empty($cn_recipients_array[0])) {
	$cn_recipients_array = array();
}

switch ($action) {
	case 'add_email':
		$message_type = "";
		
		if (empty($email_address)) {
			$message_type = "error";
			$message = "You must enter an email address.";
		} else {
			if (!is_email($email_address)) {
				$message_type = "error";
				$message = "You must enter a valid email address.";
			} else {
				if (check_dupes($email_address, $cn_recipients_array)) {
					$message_type = "error";
					$message = "That email address is already on the list.";
				}
			}
		}
		
		
		if ($message_type != "error") {  //if there are no errors
			$cn_recipients_array[] = $email_address;
			$cn_recipients = implode(",", $cn_recipients_array);
		
			if (update_option("cn_recipients", $cn_recipients)) {
				$message_type = "success";
				$message = "User " . $email_address . " has been added and will receive new comment notifications.";
			} else {
				$message_type = "error";
				$message = "There was a problem adding " . $email_address . " to the recipient list.  Please check your database and try again.";
			}
		}
		
		$cn_recipients = get_option("cn_recipients");
		$cn_recipients_array = explode(",", $cn_recipients);
		add_action('admin_menu', 'add_nav_option');
		break;
		
	




	case 'add_options':
		$message_type = "";
		if ($moderated_only != $moderated_only_option) {	
			if (update_option("cn_moderated_only", $moderated_only)) {
				$message_type = "success";
				$message = "Notification options have been updated.";
			} else {
				$message_type = "error";
				$message = "There was a problem updating notification options.  Please try again.";
			}
		} else {
			$message_type = "success";
			$message = "Notification options have been updated.";
		}
		
		$moderated_only_option = get_option("cn_moderated_only");
		add_action('admin_menu', 'add_nav_option');
		break;
	
	case 'remove_email':
		$new_recipients = array();
		
		foreach ($cn_recipients_array as $email) {
			if ($email != $email_address) {
				$new_recipients[] = $email;
			}
		}
		
		$cn_recipients = implode(",", $new_recipients);
		
		if (update_option("cn_recipients", $cn_recipients)) {
			$message_type = "success";
			$message = "User " . $email_address . " has been removed and will no longer receive new comment notifications.";
		} else {
			$message_type = "error";
			$message = "There was a problem removing " . $email_address . " from the recipient list.  Please check your database and try again.";
		}
		
		$cn_recipients = get_option("cn_recipients");
		$cn_recipients_array = explode(",", $cn_recipients);
		add_action('admin_menu', 'add_nav_option');
		break;
		
	case 'showForm':
		add_action('admin_menu', 'add_nav_option');
		break;
}




function add_nav_option() {
	add_options_page('Comment Notifier', 'Comment Notifier', 8, 'comment_notifier', 'comments_notifier');
}


function cn_show_form($cn_recipients, $cn_recipients_array, $moderated_only_option) {

	if ($moderated_only_option == 1) {
		$checked = ' checked="checked"';
	} else {
		$checked = "";
	}
	
	echo '<a name="main"></a><fieldset class="options"><legend>New Email Address</legend>';
	echo '<form name="cn_add_email" action="'. $_SERVER["REQUEST_URI"] . '" method="post">';
	echo '<input name="action" value="add_email" type="hidden" />';
	echo '<label for="email_address">Email Address</label>';
	echo " <input type=\"text\" name=\"email_address\" id=\"email_address\" value=\"$email_address\" />";
	echo '<div class="submit"><input type="submit" value="Add New Email Address" /></div>';
	echo "</form>\n";
	echo '</fieldset>';
	
	echo '<a name="main"></a><fieldset class="options"><legend>Notification Options</legend>';
	echo '<form name="cn_add_options" action="'. $_SERVER["REQUEST_URI"] . '" method="post">';
	echo '<input name="action" value="add_options" type="hidden" />';
	echo "<input type=\"checkbox\" name=\"moderated_only\" id=\"moderated_only\" value=\"1\"" . $checked . "/> ";
	echo '<label for="moderated_only">Only send notifications for comments held for moderation</label>';
	echo '<div class="submit"><input type="submit" value="Update Notification Options" /></div>';
	echo "</form>\n";
	echo '</fieldset>';
	
	echo '<fieldset class="options"><h3>Current Notification List</h3>';
		if (empty($cn_recipients)) {
			echo "No current recipients.";
		} else {
			echo "<ul>";
				foreach ($cn_recipients_array as $email) {
					echo '<li>' . $email . "\n";
					echo '<form name="cn_remove_email" action="'. $_SERVER["REQUEST_URI"] . '" method="post">' . "\n";
					echo '<input type="hidden" name="action" value="remove_email" />' . "\n";
					echo '<input type="hidden" name="email_address" value="'. $email . '" />' . "\n";
					echo '<input type="submit" value="remove" /></form></li>' . "\n";
				}
			echo "</ul>\n";
		}
	echo "</fieldset>\n";
}

function comments_notifier() {
	show_comments_notifier();
}

function show_comments_notifier($message_type='', $message='') {
	global $message_type;
	global $message;
	global $cn_recipients;
	global $cn_recipients_array;
	global $moderated_only_option;
	
	echo '<div class="wrap">';
	echo "<h2>Comment Notifier</h2>\n";
	if (!empty($message_type)) {
		if ($message_type == "error") {
			echo '<div class="error">' . $message . '</div>';
		} elseif ($message_type == "success") {
			echo '<div class="updated">' . $message . '</div>';
		}
	}

	cn_show_form($cn_recipients, $cn_recipients_array, $moderated_only_option);
	echo "</div>\n";
}


function wp_notify_allmods($comment_id) {
	global $wpdb;
	
	$comment = $wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID='$comment_id' LIMIT 1");
	$post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID='$comment->comment_post_ID' LIMIT 1");
	$blogname = get_option('blogname');
	$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
	
	$moderated_only_option = get_option("cn_moderated_only");
	
	if (get_option('comment_moderation') == 1) {  //if we need to send comment moderation email
		$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");
		$notify_message  = sprintf( __('A new comment on the post #%1$s "%2$s" is waiting for your approval'), $post->ID, $post->post_title ) . "\r\n";
		$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
		$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
		$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
		$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
		$notify_message .= sprintf( __('Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=%s'), $comment->comment_author_IP ) . "\r\n";
		$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
		$notify_message .= sprintf( __('Approve it: %s'),  get_option('siteurl')."/wp-admin/comment.php?action=mac&c=$comment_id" ) . "\r\n";
		$notify_message .= sprintf( __('Delete it: %s'), get_option('siteurl')."/wp-admin/comment.php?action=cdc&c=$comment_id" ) . "\r\n";
		$notify_message .= sprintf( __('Spam it: %s'), get_option('siteurl')."/wp-admin/comment.php?action=cdc&dt=spam&c=$comment_id" ) . "\r\n";
		$notify_message .= sprintf( __('Currently %s comments are waiting for approval. Please visit the moderation panel:'), $comments_waiting ) . "\r\n";
		$notify_message .= get_option('siteurl') . "/wp-admin/moderation.php\r\n";
		$subject = sprintf( __('[%1$s] Please moderate: "%2$s"'), get_option('blogname'), $post->post_title );
		$notify_message = apply_filters('comment_moderation_text', $notify_message, $comment_id);
		$subject = apply_filters('comment_moderation_subject', $subject, $comment_id);
		$message_headers = "";
		
		$cn_recipients = get_option("cn_recipients");
		$cn_recipients_array = explode(",", $cn_recipients);
		
		foreach ($cn_recipients_array as $email) {
			@wp_mail($email, $subject, $notify_message, $message_headers);
		}
	} else {  //or just a regular "you've got a new comment" email
		if ($moderated_only_option != 1) { //if comments are not modded but update notifications are set to moderated only don't send
			$notify_message  = sprintf( __('New comment on your post #%1$s "%2$s"'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
			$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= sprintf( __('Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=%s'), $comment->comment_author_IP ) . "\r\n";
			$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$notify_message .= __('You can see all comments on this post here: ') . "\r\n";
			$subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
			$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
			$notify_message .= sprintf( __('Delete it: %s'), get_option('siteurl')."/wp-admin/comment.php?action=cdc&c=$comment_id" ) . "\r\n";
			$notify_message .= sprintf( __('Spam it: %s'), get_option('siteurl')."/wp-admin/comment.php?action=cdc&dt=spam&c=$comment_id" ) . "\r\n";
			$notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
			$subject = apply_filters('comment_notification_subject', $subject, $comment_id);
			$message_headers = "MIME-Version: 1.0\n"
				. "$from\n"
				. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
			$message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);
		
			$cn_recipients = get_option("cn_recipients");
			$cn_recipients_array = explode(",", $cn_recipients);
			
			foreach ($cn_recipients_array as $email) {
				@wp_mail($email, $subject, $notify_message, $message_headers);
			}
		}
	}
	
	return true;
}


function check_dupes($email_address, $cn_recipients_array) {
	$has_dupes = false;
	foreach ($cn_recipients_array as $email) {
		if ($email == $email_address) {
			$has_dupes = true;
			break 1;
		}
	}
	
	return $has_dupes;
}
add_action('comment_post', 'wp_notify_allmods');

?>
<?php
/*Removed email notification on new post or modification of existing post - Lane Denson */
?>