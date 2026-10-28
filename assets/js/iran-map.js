/**
 * نقشه حرارتی استان‌های ایران — مشتریار
 *
 * داده پایتخوان‌ها از window.MOSHTARYAR_IRAN_PATHS (فایل iran-paths.js)
 * و داده آماری از window.moshtaryarMapData (wp_localize_script) می‌آید.
 */
/* global window, document */
( function () {
	'use strict';

	var paths = window.MOSHTARYAR_IRAN_PATHS || [];
	var conf = window.moshtaryarMapData || {};
	var data = conf.provinces || {};
	var i18n = conf.i18n || {};
	var colors = conf.colors || [ '#fef3c7', '#fde68a', '#f59e0b', '#dc2626', '#991b1b' ];
	var metric = ( conf.metric === 'revenue' ) ? 'revenue' : 'orders';

	/**
	 * قالب‌بندی عدد با جداکننده هزارگان فارسی.
	 *
	 * @param {number} n عدد ورودی.
	 * @return {string}
	 */
	function faNum( n ) {
		var digits = '۰۱۲۳۴۵۶۷۸۹';
		return String( Math.round( n ) )
			.replace( /\d/g, function ( d ) { return digits[ + d ]; } )
			.replace( /\B(?=(\d{3})+(?!\d))/g, '،' );
	}

	/**
	 * قالب‌بندی مبلغ به تومان.
	 *
	 * @param {number} n عدد ورودی.
	 * @return {string}
	 */
	function faMoney( n ) {
		if ( n >= 1000000000 ) {
			return faNum( n / 1000000 ) + ' م ت';
		}
		if ( n >= 1000000 ) {
			return faNum( n / 1000000 ) + ' میلیون تومان';
		}
		return faNum( n ) + ' تومان';
	}

	/**
	 * مرزهای پنج سطل رنگی از مقادیر استان‌ها.
	 *
	 * @param {string} key 'orders' یا 'revenue'.
	 * @return {number[]} چهار مرز میانی صعودی.
	 */
	function thresholds( key ) {
		var vals = [];
		Object.keys( data ).forEach( function ( code ) {
			if ( data[ code ][ key ] > 0 ) {
				vals.push( data[ code ][ key ] );
			}
		} );
		if ( ! vals.length ) {
			return [ 1, 2, 3, 4 ];
		}
		vals.sort( function ( a, b ) { return a - b; } );
		return [ 0, 1, 2, 3 ].map( function ( i ) {
			return vals[ Math.floor( ( i + 1 ) * vals.length / 5 ) ];
		} );
	}

	/**
	 * رنگ هر استان بر اساس مقدار.
	 *
	 * @param {number}   v    مقدار.
	 * @param {number[]} cuts مرزها.
	 * @return {string}
	 */
	function colorFor( v, cuts ) {
		var i = 0;
		while ( i < cuts.length && v > cuts[ i ] ) {
			i++;
		}
		return colors[ Math.min( i, colors.length - 1 ) ];
	}

	/**
	 * ساخت SVG نقشه داخل ظرف.
	 *
	 * @param {string} key 'orders' یا 'revenue'.
	 * @return {void}
	 */
	function render( key ) {
		var svg = document.getElementById( 'moshtaryar-iran-svg' );
		if ( ! svg ) {
			return;
		}
		var cuts = thresholds( key );
		var fmt = 'revenue' === key ? faMoney : faNum;
		var ns = 'http://www.w3.org/2000/svg';
		var tip = document.getElementById( 'moshtaryar-map-tip' );

		while ( svg.firstChild ) {
			svg.removeChild( svg.firstChild );
		}

		paths.forEach( function ( p ) {
			var row = data[ p.id ] || { orders: 0, customers: 0, revenue: 0, name_fa: p.fa };
			var el = document.createElementNS( ns, 'path' );
			el.setAttribute( 'd', p.d );
			el.setAttribute( 'fill', colorFor( row[ key ], cuts ) );
			el.setAttribute( 'class', 'moshtaryar-iran-province' );
			el.setAttribute( 'tabindex', '0' );

			var label = p.fa + ' — ' + fmt( row[ key ] );

			function show( ev ) {
				if ( ! tip ) {
					return;
				}
				tip.textContent = label;
				tip.style.display = 'block';
				var rect = svg.getBoundingClientRect();
				var x = ( ev.clientX - rect.left ) + 14;
				var y = ( ev.clientY - rect.top ) + 14;
				if ( x > rect.width - 160 ) {
					x -= 180;
				}
				tip.style.left = x + 'px';
				tip.style.top = y + 'px';
			}
			function hide() {
				if ( tip ) {
					tip.style.display = 'none';
				}
			}

			el.addEventListener( 'mousemove', show );
			el.addEventListener( 'mouseenter', show );
			el.addEventListener( 'mouseleave', hide );
			el.addEventListener( 'blur', hide );
			el.addEventListener( 'focus', function () {
				var r = svg.getBoundingClientRect();
				show( { clientX: r.left + r.width / 2, clientY: r.top + r.height / 2 } );
			} );

			svg.appendChild( el );
		} );

		// لجند.
		var legend = document.getElementById( 'moshtaryar-map-legend' );
		if ( legend ) {
			legend.textContent = '';
			for ( var i = colors.length - 1; i >= 0; i-- ) {
				var chip = document.createElement( 'span' );
				chip.className = 'moshtaryar-legend-chip';
				chip.style.background = colors[ i ];
				legend.appendChild( chip );
			}
		}
	}

	/**
	 * اتصال دکمه‌های سوییچ و رندر اولیه.
	 *
	 * @return {void}
	 */
	function init() {
		if ( ! paths.length ) {
			return;
		}
		var buttons = document.querySelectorAll( '[data-moshtaryar-metric]' );
		Array.prototype.forEach.call( buttons, function ( btn ) {
			btn.addEventListener( 'click', function () {
				metric = btn.getAttribute( 'data-moshtaryar-metric' );
				Array.prototype.forEach.call( buttons, function ( b ) {
					b.classList.toggle( 'is-active', b === btn );
				} );
				render( metric );
			} );
		} );
		render( metric );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
