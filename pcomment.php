<?php
/*
Plugin Name: Paragraph Comment
Plugin URI: http://cookie-lab.miraiserver.com/
Description: Allows comments by paragraph
Version: 0.0
Author: Jin Sakuma

Copyright: Jin Sakuma
*/

class ParagraphComment {
	function __construct () {
		// Head
		add_action ('wp-head', array(&$this, 'head'));
		
		// Style and JavaScript
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue'));
		
		// Add Comments
		remove_filter('the_content', 'wpautop');
		add_filter('the_content', array(&$this, 'add_comments'));
		
		// Attach Paragraph Info
		add_action('comment_post', array(&$this, 'attach_paragraph_info'));
		
		// Add Footer
		add_action('wp_footer', array(&$this, 'footer'));
	}
	
	function enqueue () {
		$plugin_url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		
		wp_enqueue_script('pcomment_js', $plugin_url.'/js/script.js', array('jquery'));
		if (is_user_logged_in()) {
			wp_enqueue_script('pcomment_admin_js', $plugin_url.'/js/admin.js', array('jquery'));
		}
		
		wp_enqueue_style('pcomment_css', $plugin_url.'/css/style.css');
		if (is_user_logged_in()) {
			wp_enqueue_style('pcomment_admin_css', $plugin_url.'/css/admin.css');
		}
	}
	
	function add_comments ($pee, $br = true) {
		$pre_tags = array();

		if ( trim($pee) === '' )
			return '';

		$pee = $pee . "\n"; // just to make things a little easier, pad the end

		if ( strpos($pee, '<pre') !== false ) {
			$pee_parts = explode( '</pre>', $pee );
			$last_pee = array_pop($pee_parts);
			$pee = '';
			$i = 0;

			foreach ( $pee_parts as $pee_part ) {
				$start = strpos($pee_part, '<pre');

				// Malformed html?
				if ( $start === false ) {
					$pee .= $pee_part;
					continue;
				}

				$name = "<pre wp-pre-tag-$i></pre>";
				$pre_tags[$name] = substr( $pee_part, $start ) . '</pre>';

				$pee .= substr( $pee_part, 0, $start ) . $name;
				$i++;
			}

			$pee .= $last_pee;
		}

		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
		// Space things out a little
		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|noscript|legend|section|article|aside|hgroup|header|footer|nav|figure|details|menu|summary)';
		$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
		$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
		
		if ( strpos( $pee, '</object>' ) !== false ) {
			// no P/BR around param and embed
			$pee = preg_replace( '|(<object[^>]*>)\s*|', '$1', $pee );
			$pee = preg_replace( '|\s*</object>|', '</object>', $pee );
			$pee = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee );
		}

		if ( strpos( $pee, '<source' ) !== false || strpos( $pee, '<track' ) !== false ) {
			// no P/BR around source and track
			$pee = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee );
			$pee = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee );
			$pee = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee );
		}

		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
		// make paragraphs, including one at the end
		$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
		$pee = '';
		
		
		// ---------- Here We Actually Add Ps ----------
		$i = 0;
		foreach ( $pees as $tinkle ) {
			$pee .= $this->before_paragraph($i) . trim($tinkle, "\n") . $this->after_paragraph($i);
			$i++;
		}

		$pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
		$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

		if ( $br ) {
			$pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', '_autop_newline_preservation_helper', $pee);
			$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
			$pee = str_replace('<WPPreserveNewline />', "\n", $pee);
		}

		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
		$pee = preg_replace( "|\n</p>$|", '</p>', $pee );

		if ( !empty($pre_tags) )
			$pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);

		return $pee;
	}
	
	private function before_paragraph ($paragraph_num) {
		return '<div id="pcomment_p_'.$paragraph_num.'" class="pcomment_p"><p class="pcomment_innerp">';
	}
	
	private function after_paragraph ($paragraph_num) {
		$obj_comments = get_comments(array(
			'meta_key' => 'paragraph',
			'meta_value' => $paragraph_num
		));
		
		$comments = array();
		foreach ($obj_comments as $obj_comment) {
			$user = get_user_by('slug', $obj_comment->comment_author);
			$comments[] = array(
				'author' => $user->get('display_name'),
				'date' => $obj_comment->comment_date,
				'content' => $obj_comment->comment_content
			);
		}
		
		if (!$comments && !is_user_logged_in()) {
			$str = '</p></div>';
		} else {
			$str = '</p><div id="pcomment_comment_'.$paragraph_num.'" class="pcomment_commentbar">'.
				   '<div class="pcomment_json">'.json_encode($comments, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</div>'.
				   '<input type="hidden" name="paragraph" value="'.$paragraph_num.'" />'.
				   '</div></div>';	// Comment
		}
		return $str;
	}

	function attach_paragraph_info ($id) {
		if (isset($_POST['paragraph'])) {
			add_comment_meta($id, 'paragraph', $_POST['paragraph']);
		}
	}

	function footer () {
		?>
		<div id="pcomment_filter"></div>
		<div id="pcomment_popup" class="box">
			<h2>コメント</h2>
			<div id="comment_list">
				<div id="talk"></div>
				<?php
				if (is_user_logged_in()) {
					comment_form(array(
						'comment_field' => '<textarea id="comment_body" name="comment">コメント</textarea>',
						'title_reply' => '',
						'comment_notes_after' => '',
						'logged_in_as' => '',
						'label_submit' => '投稿',
						'id_form' => 'comment_form'
					));
				}
				?>
			</div>
		</div>
		<?php
	}
}

$pComment = new ParagraphComment();
?>