jQuery(function () {
	// Adjust Popup Size
	jQuery('#pcomment_popup').css({
		'height': screen.height * 0.7,
		'width': screen.width * 0.6,
		'margin-top': - screen.height * 0.7 * 0.5,
		'margin-left': - screen.width * 0.6 * 0.5
	});
	
	// Adjust Comment Bar
	for (var i = 0; i < jQuery('.pcomment_p').size(); i++) {
		var parent = jQuery('.pcomment_p')[i];
		
		/***** Fix Size *****/
		var height = jQuery('p', parent).height() - 2;
		jQuery('.pcomemnt_commentbar', parent).css('max-height', height);
		
		/***** Load Comments *****/
		var comments = jQuery('.pcomment_json', parent).html();
		
		if (comments) {
			comments = JSON.parse(comments);
			var comment_list = jQuery('.pcomment_commentbar', parent).append('<ul class="pcomment_list"></ul>').children('.pcomment_list');
			
			for (var num = 0; num < comments.length; num++) {
				comment_list.append(
					'<li><span class="pcomment_author">' + comments[num].author + '</span>' +
					'<div class="pcomment_content">' + commentContentFilter(comments[num].content) + '</div></li>'
				);
			}
		}
	}
	
	// Popup
	jQuery('.pcomment_commentbar').click(function(){
		var paragraph = jQuery('input[name=paragraph]', this).val();
		
		// If it has been popped up
		if (jQuery('#pcomment_popup input[name=paragraph]').size()) {
			jQuery('#pcomment_popup input[name=paragraph]').remove();
			jQuery('#pcomment_popup #talk ul').remove();
			jQuery('#pcomment_popup #comment_body').html('コメント');
		}
		
		jQuery('#pcomment_popup #comment_form').append('<input type="hidden" name="paragraph" value="' + paragraph + '" />');
		/***** Display Comments *****/
		var comments = jQuery('#pcomment_p_'+paragraph+' .pcomment_json').html();
		comments = JSON.parse(comments);
		if (comments) {
			var comment_list = jQuery('#talk').append('<ul></ul>').children('ul');
			
			var authorBefore;
			for (var num = 0; num < comments.length; num++) {
				var comment = 
					'<li>'+
						'<div class="comment_info clearfix">';
				
				if (authorBefore !== comments[num].author) {
					comment += '<div class="comment_author">' + comments[num].author + '</div>';
				}
				
				comment +=
							'<div class="comment_time">' + comments[num].date + '</div>' +
						'</div>' +
						'<div class="comment_content">' + commentContentFilter(comments[num].content) + '</div>' +
					'</li>';
				comment_list.append(
					comment
				);
				authorBefore = comments[num].author;
			}
		}
		// Display comments
		var comments = jQuery('#pcomment_p_'+paragraph+' .pcomment_json').html();
		comments = JSON.parse(comments);
		console.log(comments);
		if (comments) {
			var comment_list = jQuery('#pcomment_popup #talk').append('<ul></ul>').children('ul');
			
			var authorBefore;
			for (var num = 0; num < comments.length; num++) {
				var comment = 
					'<li>'+
						'<div class="comment_info clearfix">';
				
				if (authorBefore !== comments[num].author) {
					comment += '<div class="comment_author">' + comments[num].author + '</div>';
				}
				
				comment +=
							'<div class="comment_time">' + comments[num].date + '</div>' +
						'</div>' +
						'<div class="comment_content">' + commentContentFilter(comments[num].content) + '</div>' +
					'</li>';
				comment_list.append(
					comment
				);
				authorBefore = comments[num].author;
			}
		}
		
		// Popup
		jQuery('#pcomment_popup').css('display', 'block');
		jQuery('#pcomment_filter').css('display', 'block');
	});
	
	// Close Popup
	jQuery('#pcomment_filter').click(function() {
		jQuery('#pcomment_popup').css('display', 'none');
		jQuery('#pcomment_filter').css('display', 'none');
	})
});

function commentContentFilter(content) {
	content += '<p class="comment_paragraph">';
	content = content.replace(/\r?\n/g, '</p><p class="comment_paragraph">');
	return content;
}