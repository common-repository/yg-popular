window.ygPop = window.ygPop || {};
(function($,ygPop){
	ygPop = {
		pop_notify: function(mssg,time){
			var msgDiv = document.createElement('div');
			msgDiv.setAttribute('id','pop_msg_div');
			var txt = document.createTextNode(mssg);
			msgDiv.appendChild(txt);
			$('body')[0].appendChild(msgDiv);
			$('#wpwrap').fadeTo(400,0.2);
			$('#pop_msg_div').fadeTo(400,1);
			setTimeout(function(){
  				$('#wpwrap').fadeTo(400,1);
				$('#pop_msg_div').fadeTo(400,0,function(){
					$('#pop_msg_div').remove();
				});
			},time);
		}
	}
	$(document).ready(function() {
		$('#pop_duration_disabl').change(function(){
			var checked = $(this).prop('checked');
			$('#pop_w_duration').prop('disabled',checked);
			$('#pop_w_duration2').prop('disabled',checked);
		})
		$('#update_count').submit(function(e){
			e.preventDefault();
			var curpviews=$('#yg_pop_add_v_post_name').attr('views');
			var pviews=$('#yg_pop_allpost_count').val();
			var pttl=$('#yg_pop_add_v_post_name').attr('ttl')
			var addviews = true;
			if(curpviews > pviews){
				addviews = window.confirm('Are you sure you want to decrease views for "'+pttl+'"?');
			}
			var _wpnonce=$('#update_count').find('input[name="_wpnonce"]').val();
			if(addviews){
				var data={postid:$('#yg_pop_add_v_post_id').val(),postttl:pttl,view_count:pviews,_wpnonce:_wpnonce,action:'yg_update_post_count'}
				$.ajax({
					url:ajaxurl,
					data:data,
					type:"POST",
					success:function(data){
						var rep = jQuery.parseJSON(data);
						ygPop.pop_notify(rep['mssg'],2000);
						$('#most_pop_tbl_div').html(rep['tbl']);
						if($('#most_pop_tbl_div2').length)
							$('#most_pop_tbl_div2').html(rep['tbl2']);
						if($('#most_pop_tbl_div3').length)
							$('#most_pop_tbl_div3').html(rep['tbl3']);
						/*
						var slctdOp = $('#yg_pop_allpost').find('option:selected');
						slctdOp.attr('p_count',pviews);
						var newCountTxt = slctdOp.text();
						slctdOp.text(newCountTxt.replace(/\([0-9]*\sviews/i,'('+pviews+' views'));
						$("#yg_pop_allpost option:first").attr('selected','selected');
						$('#yg_pop_allpost_count').val('');
						*/
					}
				});
			}
		});
		$('.datepick').datepicker({
			'dateFormat':'M d, yy'
		});
		$('#remove_rec').click(function(e){
			e.preventDefault();
			var remv=window.confirm("Are you sure you want to remove records?");
			if($('#rec_date').val()!='' && remv){
				var data={refrom:$('#rec_date').val(),_wpnonce:$(this).attr('pop_nonce'),action:'yg_re_rec_from'}
				$.ajax({
					url:ajaxurl,
					data:data,
					type:"POST",
					success:function(data){
						$('#rec_date').val('');
						ygPop.pop_notify(data,2000);
					}
				});
			}
		});
		$('#yg_pop_add_v_post_name').autocomplete({
			source:ajaxurl + '?action=yg_pop_get_posts&fltr=',
			minLength:3,
			select: function(event,ui){$('#yg_pop_add_v_post_name').attr('ttl',ui.item.value);$('#yg_pop_add_v_post_id').val(ui.item.id);$('#yg_pop_add_v_post_name').attr('views',ui.item.views);}
		});
		$('#yg_pop_filter_ptype').change(function(){
			$('#yg_pop_add_v_post_name').autocomplete('option','source',ajaxurl + '?action=yg_pop_get_posts&fltr=' + $('#yg_pop_filter_ptype').val());
		});
		$('#clr_wdgt_cache').click(function(e){
			e.preventDefault();
			var remv=window.confirm("Are you sure you want to delete cache?");
			if(remv){
				var data={_wpnonce:$(this).attr('pop_nonce'),action:'yg_pop_clr_wdgtcache'}
				$.ajax({
					url:ajaxurl,
					data:data,
					type:"POST",
					success:function(data){
						ygPop.pop_notify(data,2000);
					}
				});
			}
		});
	});
})(jQuery,window.ygPop);