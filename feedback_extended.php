<?php
/* * ***
 *
 * Plugin name:     	Feedback Extended
 * Description:     	This plugin provides extended feature (Reply) to JetPack feedback plugin.
 * Version:         	1.0.0
 * Author:          	Ehsanul Haque
 * Author URI:      	http://ehsanIs.me/
 * Requires at least: 	2.8
 * Tested up to: 		3.5.1
 * Stable Tag: 			1.0.0
 * License: 			GPLv2
 * Donate link: 		http://ehsanis.me/donate/ 
 *
 */

add_action('manage_posts_custom_column', 'feedback_extended_columns', 11, 2);
add_action('wp_ajax_send_feedback_reply', 'feedback_extended_send_feedback_reply');

function feedback_extended_send_feedback_reply() {
  $postID = $_POST['post_id'];
  $postData = get_post($postID);
  $postAuthor = get_post_meta($postID, '_feedback_author', TRUE);
  $postContent_ = $postData->post_content;
  if ( preg_match('/<!--more(.*?)?-->/', $postContent_, $matches) ) {
    $postContent = explode($matches[0], $postContent_, 2);
    $initalMessage = '<p><i style="font-size: 11px;">--<br/>' . $postAuthor . ' wrote: <br/>"' . $postContent[0] . '"</i></p>';
  } else {
    $initalMessage = '';
  }
  $headers[] = 'From: ' . $_POST['sender_name'] . ' <' . $_POST['sender_email'] . '>';
  $headers[] = 'Cc: ' . $_POST['sender_email'];
  add_filter('wp_mail_content_type', create_function('', 'return "text/html";'));
  wp_mail($_POST['recipient_email'], $_POST['feedback_subject'], stripslashes(nl2br($_POST['reply_message'])) . stripslashes(nl2br($initalMessage)), $headers);
}

function feedback_extended_columns($col) {
  global $post;


  switch ($col) {
    case 'feedback_from' :
      break;
    case 'feedback_message' :
      $replied_flag = get_post_meta($post->ID, '_fe_feedback_replied', TRUE);

      if (!isset($replied_flag) || $replied_flag != TRUE) {
        if ($post->post_status == 'publish') {
          echo " | <span class='reply'> <a class='submitdelete' title='";
          echo esc_attr(__('Reply to this message', 'jetpack'));
          echo "' onclick='feFeedbackReply( $post->ID );return false;' href='#'";
          echo "'>" . __('Reply', 'jetpack') . "</a></span>";
          echo "</div>";
          fe_feedback_reply($post->ID, $post);
        }
      } else {
        if ($post->post_status == 'publish') {
          echo " | <span class='reply'> <a class='submitdelete' title='";
          echo esc_attr(__('No Action Required', 'jetpack'));
          echo "'>" . __('Reply Sent', 'jetpack') . "</a></span>";
          echo "</div>";
        }
      }
      break;
  }
}

function fe_feedback_reply($postID, $post) {
  global $post;
  $current_user_info = get_userdata(wp_get_current_user()->ID);
  $author_name = get_post_meta($postID, '_feedback_author', TRUE);
  $author_email = get_post_meta($postID, '_feedback_author_email', TRUE);
  $author_url = get_post_meta($postID, '_feedback_author_url', TRUE);
  $author_ip = get_post_meta($postID, '_feedback_ip', TRUE);
  $form_url = get_post_meta($postID, '_feedback_contact_form_url', TRUE);
  $content = sanitize_text_field(get_the_content(''));
  $replied_flag = get_post_meta($postID, '_fe_feedback_replied', TRUE);
  $replied_content = get_post_meta($postID, '_fe_feedback_replied_content', TRUE);
  $feedback_subject = esc_html(get_post_meta($postID, '_feedback_subject', TRUE));

  if ($mode == 'single') {
    $wp_list_table = _get_list_table('WP_Post_Comments_List_Table');
  } else {
    $wp_list_table = _get_list_table('WP_Comments_List_Table');
  }

  if ($table_row) :
    ?>
    <table style="display:none;" id="fe_reply_area_<?= $postID ?>"><tbody id="com-reply"><tr id="replyrow" style="display:none;"><td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="colspanchange">
            <?php
          else :
            ?>
            <div id="com-reply-<?= $postID ?>" style="display:none;"><div id="replyrow">
              <?php
              endif;
              ?>
              <div id="replyhead" style="margin-top: 10px;"><h5><?php _e('Reply to Feedback'); ?></h5></div>
              <div id="edithead" style="background: #83BA3C; color: #fff;">
                <div class="inside">
                  <label for="sender-name"><?php _e('Sender\'s Name') ?></label>
                  <input type="text" name="feedback_reply_seconder_name" size="50" value="<? echo $current_user_info->display_name; ?>" tabindex="101" id="sender-name-<?= $postID ?>" />
                </div>

                <div class="inside">
                  <label for="sender-email"><?php _e('Sender\'s E-mail') ?></label>
                  <input type="text" name="feedback_reply_seconder_email" size="50" value="<? echo $current_user_info->user_email; ?>" tabindex="102" id="sender-email-<?= $postID ?>" />
                </div>

                <div style="clear:both;"></div>
              </div>

              <div id="edithead" style="background: #1f3f7f; color: #fff; margin-top: 5px;">
                <div class="inside">
                  <label for="recipient-name"><?php _e('Recipient\'s Name') ?></label>
                  <input type="text" name="feedback_reply_recipient_name" size="50" value="<? echo $author_name; ?>" tabindex="101" id="recipient-name-<?= $postID ?>" readonly />
                </div>

                <div class="inside">
                  <label for="recipient-email"><?php _e('Recipient\'s E-mail') ?></label>
                  <input type="text" name="feedback_reply_recipient_email" size="50" value="<? echo $author_email; ?>" tabindex="102" id="recipient-email-<?= $postID ?>" readonly />
                </div>

                <div style="clear:both;"></div>
              </div>


              <div id="replycontainer">
                <?php
                $reply_initial_content = '';//'<p><i style="font-size: 11px;">--<br/>' . $author_name . ' wrote: <br/>"' . addslashes($content) . '"</i></p>';
                if ($replied_flag == TRUE) {
                  //TODO: May show the reply message
                } else {
                  $quicktags_settings = array('buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,spell,close');
                  wp_editor('', 'replycontent' . $postID, array('textarea_id' => 'replycontent-' . $postID, 'textarea_name' => 'replycontent-' . $postID, 'media_buttons' => false, 'tinymce' => true, 'quicktags' => $quicktags_settings, 'tabindex' => 104));
                }
                ?>
              </div>
              <input type='hidden' id='initial_message_<?= $postID ?>' value='<?= $reply_initial_content ?>'>
              <p id="replysubmit" class="submit">
                <a href="#comments-form" class="cancel button-secondary alignleft" tabindex="106" onclick="feFeedbackReply(<?= $postID ?>);"><?php _e('Cancel'); ?></a>
                <a href="#comments-form" id="fe_form_<?= $postID ?>_button" class="save button-primary alignright" tabindex="104">
                  <span id="replybtn"><?php _e('Send Reply'); ?></span></a>
                <img class="waiting" style="display:none;" src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" alt="" id="waiting<?= $postID ?>" />
                <span id="msg<?= $postID ?>" style="display:none;">Message Sent</span>
                <br class="clear" />
              </p>

              <?php if ($table_row) : ?>
                </td></tr></tbody></table>
              <?php else : ?>
              </div></div>
          <?php endif; ?>
          </form>

          <script type='text/javascript'>

            function feFeedbackReply(feedbackId) {
              if (document.getElementById('com-reply-'+feedbackId).style.display == 'none') {
                document.getElementById('com-reply-'+feedbackId).style.display = 'inline';
              } else {
                document.getElementById('com-reply-'+feedbackId).style.display = 'none';
              }
            }

            jQuery(document).ready( function($) {
              $('#fe_form_<?= $postID ?>_button').click(function(e) {
                document.getElementById('waiting<?= $postID ?>').style.display = 'inline';
                e.preventDefault();
                tinyMCE.triggerSave();
                $.post( ajaxurl, {
                  action: 'send_feedback_reply',
                  post_id: <?= $postID ?>,
                  sender_name: $('#sender-name-<?= $postID ?>').val(),
                  sender_email: $('#sender-email-<?= $postID ?>').val(),
                  recipient_name: $('#recipient-name-<?= $postID ?>').val(),
                  recipient_email: $('#recipient-email-<?= $postID ?>').val(),
                  reply_message: $('#replycontent<?= $postID ?>').val(),
                  initial_message: $('#initial_message_<?= $postID ?>').val(),
                  feedback_subject: "<?php echo 're: ' . $feedback_subject; ?>"
                },
                function(r) {
                  document.getElementById('waiting<?= $postID ?>').style.display = 'none';

                  document.getElementById('msg<?= $postID ?>').style.display = 'inline';
                  $('#msg<?= $postID ?>')
                  .css({backgroundColor: '#70E01B'})
                  .fadeOut(1550, function() {
                    feFeedbackReply(<?= $postID ?>);
                  });

                });
              });
            });

          </script>

<?php
}
?>
