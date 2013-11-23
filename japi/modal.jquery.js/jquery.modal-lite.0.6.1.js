/*!
 * Modal.js v0.6
 * 
 * Copyright 2013, Vladimir Kalmykov
 * This content is released under the MIT license
 * http://markdalgleish.mit-license.org
 */
(function($) {
	var modal = function(data) {
			this.disable = true;
			
			this.dom = {};
			this.data = $.extend({
				'afterfunc': function() { },
				'onAbort': function() { return true; },
				'afterContentInsering': function() { },
				'panelCss': {
					'backgroundColor': '#fff'
				},
				'backstopCss': {
					'width': '100%',
					'height': '100%',
					'position': 'fixed',
					'top': '0px',
					'left': '0px',
					'backgroundColor': 'rgba(0,0,0,0.3)',
					'opacity': 0,
					'z-index': 198
				},
				'windowName': 'noname',
				'html': 'Modal.js'
			}, data);
			
			
			this.dirty = false;
			this.set = function(key, value) {
				switch(key) {
					case 'width':
						
					break;
					case 'height':
						
					break;
				}
			}
			this.build=function() {
				var that = this;
				
				this.dom.wrapper = jQuery('<div />').appendTo(jQuery('body')).css({
					'opacity': 0,
					'z-index': 199,
					'width': '100%',
					'height': '100%',
					'position': 'absolute',
					'top': '0px',
					'left': '0px'
				});
				
				var table = jQuery('<table />', {
					'cellspacing': 0, 'cellpadding': 0, 'style': 'width:100%; height:100%;'
				}).appendTo(jQuery(this.dom.wrapper)).click(function() {
					
					that.closeunsafe();
				});
				
				var tab = jQuery('<tbody />').appendTo(table);
				
				var trtop = jQuery('<tr />').appendTo(jQuery(tab));
				var trcenter = jQuery('<tr />').appendTo(jQuery(tab));
				var trbottom = jQuery('<tr />').appendTo(jQuery(tab));
				
				jQuery('<td />', {
					'height': '1%'
				}).appendTo(jQuery(trtop)).click(function() {
					
					that.closeunsafe();
				});
				var tdtop = jQuery('<td />', {
					'height': '100%'
				}).css({
					'height': '100%',
					'vertical-align': 'middle'
				}).appendTo(jQuery(trcenter));
				jQuery('<td />', {
					'height': '1%'
				}).appendTo(jQuery(trbottom)).click(function() {
					
					that.closeunsafe();
				});
				
				var tr = jQuery('<tr />').appendTo(jQuery('<tbody />').appendTo(jQuery('<table />', {
					'cellspacing': 0, 'cellpadding': 0, 'style': 'width:100%;'
				}).appendTo(jQuery(tdtop))));
				jQuery('<td />', {
					'width': '50%'
				}).appendTo(jQuery(tr)).html('&nbsp;').click(function() {
					
				});
				this.dom.precontent = jQuery('<td />').appendTo(jQuery(tr)).css({
					'backgroundColor': 'none',
					'padding': '12px',
					'border-radius': '2px',
					'-moz-box-shadow': '0 0 10px rgba(0,0,0,0.5)',
					'border': 'none',
					'background-color': 'transparent',
				}).click(function(event) {
					event.stopPropagation();
				}); 
				this.dom.content = $("<div />").appendTo(this.dom.precontent)
				.css(this.data.panelCss);
				
				if (typeof(this.data["class"]) == "string") $(this.dom.content).addClass(this.data["class"]);
				jQuery('<td />', {
					'width': '50%'
				}).appendTo(jQuery(tr)).html('&nbsp;').click(function() {
					
				});
				
				this.reInitialHtml();
			};
			this.reInitialHtml = function() {
				
				var that = this;
				switch(typeof(this.data.html)) {
					case 'object':
						jQuery(this.data.html).appendTo(jQuery(this.dom.content));
					break;
					case 'string':
						
						jQuery(this.dom.content).html(this.data.html);
					break;
				}
				if (typeof(this.data.afterContentInsering) == 'function') setTimeout(function() {that.data.afterContentInsering(that);
				}, 300);
			
			};
			this.addClass = function(classname) {
				$(this.dom.precontent).addClass(classname);
				return this;
			};
			this.removeClass = function(classname) {
				var classname = classname || false;
				if (classname) $(this.dom.precontent).removeClass(classname);
				else $(this.dom.precontent).removeClass();
				return this;
			};
			this.reHtml = function(html) {
				$(this.dom.content).html(html);
				this.reInitialHtml();
			};
			this.enableZanaves = function(func) {
				var that = this;
				var func = func;
				setTimeout(func, 100);
				var scrtop = $("body").scrollTop();
				// @do wrap body content
				jQuery('body').children().wrapAll('<div id="_root"></div>');

				jQuery('#_root').css({
					'position': 'fixed',
					'top': 0,
					'left': 0,
					'width': '100%',
					'height': '100%',
					'overflow': 'hidden'
				});
				
				jQuery('#_root').children().wrapAll('<div></div>');
				jQuery('#_root>div').css({
					'width': '100%',
					'margin-top': (scrtop*-1)+'px'
				});
				
				this.dom.zanaves = jQuery('<div />', {
				
						
				}).appendTo('body').css(this.data.backstopCss);
				jQuery(this.dom.zanaves).click(function() {
					
					that.closeunsafe();
				});
				this.disable = false;
				jQuery(this.dom.zanaves).animate({
					'opacity': 1
				}, 250);
			}
			this.lock = function() {
				this.disabled = true;
				jQuery(this.dom.trtop).attr('disabled', 'disabled');
			}
			this.unlock = function() {
				this.disabled = false;
				jQuery(this.dom.trtop).removeAttr('disabled');
			}
			this.show = function() {
				var that = this;
				if (this.disable) return;
				jQuery(this.dom.wrapper).animate({opacity: 1}, 250, function() { that.data.afterfunc(); });
			}
			this.close = function(func, okclose) {
				
				var that = this;
				var okclose = okclose || false;
				if (this.disable) return;
				if (!okclose) {
					
				}
				this.disable = true;
				
				
					
					jQuery(this.dom.wrapper).fadeOut(200, function() {
						
						jQuery(that.dom.zanaves).stop().animate({
							'opacity': 0
						}, 250, function() {
							window.modalPopUpClosingUp = false;
							if (func) func();
							that.destroy();
						});
					});
				
				
			};
			this.closeunsafe = function() {
				
				if (typeof(this.data.onAbord) == 'function') {
					if (this.data.onAbord()) {
						this.close();
					};
				} else {
					this.close();
				};
			};
			this.destroy = function() {
				
				jQuery(this.dom.wrapper).remove();
				this.destroyZanaves();
				
				delete(this);
			}
			
			this.destroyZanaves = function() {
				jQuery(this.dom.zanaves).remove();
				
				
				jQuery("#_root>div").children().appendTo(jQuery('body'));
				$("body").animate({
					'scrollTop':((parseInt(jQuery("#_root>div").css("margin-top"))*-1)+'px')
				}, 1);
				jQuery("#_root").remove();
			}
			
			var that = this;
			
			this.enableZanaves(function() {
				that.show();
			});
			this.build();
	};
	
	$.fn.modal = function(options) {
		var options = options || {};
		
		return $(this).each(function() {
			new modal($.extend({
				'html': $(this).html()
			}, options))
		});
	};
	
	$.modal = function(options) {
		var options = options || {};
		return new modal(options)
	};
})(jQuery);