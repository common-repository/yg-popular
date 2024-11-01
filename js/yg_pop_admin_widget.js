(function($){
	$(document).ready(function() {
		$('.widgets-holder-wrap').on('change','.widget.open .pop_ptype_select',function(){
			var _this = $(this);
			var parnt = $(this).parents('.widget-content');
			var data={cpt:$(this).val(),_wpnonce:$(this).attr('pop_nonce'),action:'yg_get_cpt_terms'}
			$.ajax({
				url:ajaxurl,
				data:data,
				type:"POST",
				dataType:"json",
				success:function(data){
					parnt.find('.pop_tax_select').find('option').not(':first').remove().end();
					$.each(data,function(key,value){
						parnt.find('.pop_tax_select').append('<option value="'+value['taxonomy']+'">'+value['taxonomy']+'</option>');
						parnt.find('.pop_tax_select')[0].selectedIndex = 0;
					});
					parnt.find('.pop_taxval_select').find('option').not(':first').remove().end();
					parnt.find('.pop_taxval_select')[0].selectedIndex = 0;
				}
			});
		});
		$('.widgets-holder-wrap').on('change','.widget.open .pop_tax_select',function(){
			var _this = $(this);
			var parnt = $(this).parents('.widget-content');
			var data={pop_term:$(this).val(),_wpnonce:$(this).attr('pop_nonce'),action:'yg_get_terms_vals'}
			$.ajax({
				url:ajaxurl,
				data:data,
				type:"POST",
				dataType:"json",
				success:function(data){
					parnt.find('.pop_taxval_select').find('option').not(':first').remove().end();
					parnt.find('.pop_taxval_select').append(data);
					parnt.find('.pop_taxval_select')[0].selectedIndex = 0;
				}
			});
		});
		$('.widgets-holder-wrap').on('change','.widget.open .pop_select_by',function(){
			var parnt = $(this).parents('.widget-content');
			var isTagsRecent = ($(this).val()=='tags' || $(this).val()=='most recent')?true:false;
			var isTags = ($(this).val()=='tags')?true:false;
			parnt.find('.pop_dur_select').prop('disabled',isTagsRecent);
			parnt.find('.pop_tax_select').prop('disabled',isTags);
			parnt.find('.pop_taxval_select').prop('disabled',isTags);
		});
	});
})(jQuery);