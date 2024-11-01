window.ygPopDsh = window.ygPopDsh || {};
(function($,ygPopDsh){
	var graphWdt = 370;
	var graphHght = 102;
	var lingrad;
	var padding = 20;
	var j;
	var cnvsWdt = 400;
	var cnvsHght = 265;

	ygPopDsh = {
		canvas: {},
		ctx: {},
		colors: ['#026fa7','#2889bb','#5fabd3','#9bcbe4','#cae3ef'],
		postViews: [],
		postTtl: [],
		maxViews: 0,
		ajxInt: undefined,
		init:function(obj,cnvsId,postViews,postTtl){
			cnvsWdt = $('#yg_pop_dashboard_widget .inside').width();
			graphWdt = cnvsWdt-30;
			if(!$('#'+cnvsId).length){
				this.canvas = document.createElement('canvas');
				this.canvas.setAttribute('id',cnvsId);
				this.canvas.setAttribute('width',cnvsWdt);
				this.canvas.setAttribute('height',cnvsHght);
				$(obj)[0].insertBefore(this.canvas,document.getElementById('dw_frm'));
			}else{
				this.canvas = document.getElementById(cnvsId);
			}
			this.postViews = postViews;
			this.maxViews = Math.round(this.postViews[0]*1.1);
			this.maxViews = this.maxViews + (8 - (this.maxViews%8));
			this.postTtl = postTtl;
			this.ctx = this.canvas.getContext("2d");
			lingrad = this.ctx.createLinearGradient(padding,0,graphWdt + padding,0);
			for(i = 1; i < 9; i++){
				var color = (i%2==0)?'#bbb':'#eee';
				lingrad.addColorStop(0.125*i,color);
			}
			this.chartdraw();
		},
		chartdraw: function(){
			this.clear();
			this.ctx.globalAlpha = 0.4;
			this.ctx.fillStyle = lingrad;
			this.rect(padding,padding,graphWdt,graphHght);
			this.ctx.globalAlpha = 1;
			this.ctx.fillStyle = 'black';
			this.addGuides();
			this.addlabels();
			this.plotdata();
		},
		clear: function(){
			window.clearTimeout(this.ajxInt);
			this.ctx.clearRect(0, 0, cnvsWdt, cnvsHght);
		},
		rect: function(x,y,w,h){
			this.ctx.beginPath();
			this.ctx.rect(x,y,w,h);
			this.ctx.closePath();
			this.ctx.fill();
			this.ctx.stroke();
		},
		rectAnimate: function(x,y,w,h,c,m,t){
			if(t>0){
				var _w = ((11-t)/10)*w;
				t=t-1;
				this.ctx.fillStyle = c;
				this.ctx.beginPath();
				this.ctx.rect(x,y,_w,h);
				this.ctx.closePath();
				this.ctx.fill();
				this.ctx.stroke();
				setTimeout(function(){
  					ygPopDsh.rectAnimate(x,y,w,h,c,m,t);
				}, 60);
			}else{
				ygPopDsh.addviews(m,y,w)
			}
		},
		addGuides: function(){
			this.ctx.strokeStyle = "black";
			this.ctx.lineWidth = 1;
			//y
			this.ctx.beginPath();
			this.ctx.moveTo(padding,padding);
			this.ctx.lineTo(padding,graphHght + padding);
			this.ctx.stroke();
			//x
			this.ctx.moveTo(padding, graphHght + padding);
			this.ctx.lineTo(graphWdt + padding,graphHght + padding);
			this.ctx.stroke();
		},
		addlabels:function(){
			this.ctx.font = "8pt Arial";
			/* y axis labels */
			this.ctx.fillText("Posts", 2, 15);
			j = 1;
			for (var i in this.postViews){
				this.ctx.fillText(j, 5, (j*20.2)+14);
				j++;
			}
			/* x axis labels */
			this.ctx.fillText("Views", 190, 148);
			this.ctx.lineWidth = 0.5;
			for(i = 1; i < 8; i++){
				var color = (i%2==0)?'#fff':'#ddd';
				this.ctx.strokeStyle = color;
				var y = ((graphWdt/8)*i)+22;
				var num = (this.maxViews / 8) * i;
				if(num>999){num = (num/1000).toFixed(1) + 'k'}
				else if(num>999999){num = (num/1000000).toFixed(2) + 'm'}
				var numWdth = this.ctx.measureText(num).width;
				this.ctx.beginPath();
		      	this.ctx.moveTo(y,20);
		      	this.ctx.lineTo(y,graphHght+18);
		      	this.ctx.stroke();
				this.ctx.fillText(num, y-(numWdth/2), 134);
			}
		},
		plotdata: function(){
			this.ctx.font = "8pt Arial";
			this.ctx.strokeStyle = "#666";
			this.ctx.lineWidth = 0.5;
			j = 0;
			for (var i in this.postViews){
				this.ctx.fillStyle = this.colors[j];
				var barLength = ((graphWdt/this.maxViews)*this.postViews[j]);
				var barY = ((j+1)*20) + 3;
				var pCount = (this.postViews[j]==1)?this.postViews[j]+' view':this.postViews[j]+' views';
				this.rectAnimate(22,barY,barLength,16,this.colors[j],pCount,10);

				this.ctx.fillStyle = this.colors[j];
				this.rect(0,((j+1)*20) + 136,14,14);
				this.ctx.fillStyle = 'black';
				this.ctx.fillText(this.postTtl[j],18,((j+1)*20) + 147);
				j++;
			}
		},
		addviews: function(pCount,barY,barLength){
			var pCountLngth = this.ctx.measureText(pCount).width;
			if(barLength-pCountLngth > 0){
				this.ctx.fillStyle = 'white';
				this.ctx.fillText(pCount,barLength-pCountLngth+20,barY+12);
			}else{
				this.ctx.fillStyle = '#666';
				this.ctx.fillText(pCount,barLength+24,barY+12);
			}
		},
		clearspecific: function(cnvsId){
			var curCanvas = document.getElementById(cnvsId);
			var curCtx = curCanvas.getContext("2d");
			curCtx.clearRect(0, 0, cnvsWdt, cnvsHght);
		},
		ajxloader: function(cnvsId){
			this.clearspecific(cnvsId);
			var curCanvas = document.getElementById(cnvsId),
			ctx = curCanvas.getContext("2d"),
			pi = Math.PI,
			xCenter = 20,
			yCenter = 20,
			radius = 15,
			startSize = radius/4,
			num=5,
			posX=[],posY=[],angle,size,i;

			this.ajxInt = window.setInterval(function() {
				num++;
				ctx.clearRect (0,0,cnvsWdt,cnvsHght);
				for (i=0; i<9; i++){
					ctx.beginPath();
					ctx.fillStyle = 'rgba(100,100,100,'+.1*i+')';
					if (posX.length==i){
						angle = pi*i*.25;
						posX[i] = xCenter + radius * Math.cos(angle);
						posY[i] = yCenter + radius * Math.sin(angle);
					}
					ctx.arc(posX[(i+num)%8]+(cnvsWdt/2-10),posY[(i+num)%8]+100,startSize/9*i,0,pi*2,1); 
					ctx.fill();
				}
			},100);
		}
	}
	$(document).ready(function() {
		if('undefined'!= typeof postsJsObj){
			$.each(postsJsObj,function(key,value){
				ygPopDsh.init($('#yg_pop_dashboard_widget .inside'),'cnvs-'+key,value.count,value.ttl);
			});
		}
		$('#dw_getres').click(function(e){
			e.preventDefault();
			var lastCnvs = $('#yg_pop_dashboard_widget canvas').last();
			var lastTtlP = $('#yg_pop_dashboard_widget .cnvs_ttl').last();
			ygPopDsh.ajxloader(lastCnvs.attr('id'));
			lastCnvs.html('Loading...');
			var data={
				ptype:$('#dw_ptype').val(),
				pdur:$('#dw_dur').val(),
				_wpnonce:$(this).attr('pop_nonce'),
				action:'yg_dashwg_get_res'
			}
			$.ajax({
				url:ajaxurl,
				data:data,
				type:"POST",
				dataType:"json",
				success:function(data){
					ygPopDsh.init($('#yg_pop_dashboard_widget .inside'),lastCnvs.attr('id'),data.count,data.ttl);
					var lastTtlPCopy = 'Most popular posts';
					if('undefined' != typeof data.type)
						lastTtlPCopy += data.type;
					if('undefined' != typeof data.days)
						lastTtlPCopy += data.days;
					lastTtlP.html(lastTtlPCopy);
				}
			});
		});
	});
})(jQuery,window.ygPopDsh);