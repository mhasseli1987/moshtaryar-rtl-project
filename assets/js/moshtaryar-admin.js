/**
 * moshtaryar-admin.js — رفتارهای عمومی و نمودارهای SVG دست‌ساز داشبورد مشتریار.
 * بدون هیچ کتابخانهٔ بیرونی؛ داده و متن‌ها از wp_localize_script (moshtaryarCharts) می‌آیند.
 */
( function() {
	'use strict';

	var chartData = window.moshtaryarCharts || {};
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var SVG_NS = 'http://www.w3.org/2000/svg';

	/**
	 * پیام فارسی از دادهٔ لوکالایز‌شده.
	 */
	function i18n( key ) {
		return ( chartData.i18n && chartData.i18n[ key ] ) ? chartData.i18n[ key ] : '';
	}

	/**
	 * ساخت عنصر SVG.
	 */
	function svgEl( tag, attrs ) {
		var el = document.createElementNS( SVG_NS, tag );
		for ( var key in attrs ) {
			if ( Object.prototype.hasOwnProperty.call( attrs, key ) ) {
				el.setAttribute( key, attrs[ key ] );
			}
		}
		return el;
	}

	/**
	 * اجرای مؤخر تا ترنزیشن‌ها پس از رندر اولیه شروع شوند.
	 */
	function nextFrame( callback ) {
		window.requestAnimationFrame( function() {
			window.requestAnimationFrame( callback );
		} );
	}

	/**
	 * تأیید پیش از ارسال فرم بازمحاسبهٔ کامل.
	 */
	function bindRecalcConfirm() {
		var form = document.getElementById( 'moshtaryar-recalc-form' );
		if ( ! form ) {
			return;
		}
		form.addEventListener( 'submit', function( event ) {
			if ( ! window.confirm( i18n( 'confirm_recalc' ) ) ) {
				event.preventDefault();
			}
		} );
	}

	/**
	 * تولتیپ مشترک کارت نمودار (فقط textContent، مطابق قاعدهٔ امنیتی SPEC).
	 */
	function makeTooltip( host ) {
		var tip = document.createElement( 'div' );
		tip.className = 'moshtaryar-tooltip';
		tip.setAttribute( 'aria-hidden', 'true' );
		host.appendChild( tip );

		function show( text, clientX, clientY ) {
			var box = host.getBoundingClientRect();
			tip.textContent = text;
			tip.style.opacity = '1';

			var x = clientX - box.left + 14;
			var y = clientY - box.top - 12;
			var maxX = box.width - tip.offsetWidth - 8;
			var maxY = box.height - tip.offsetHeight - 8;

			tip.style.left = Math.max( 8, Math.min( x, maxX ) ) + 'px';
			tip.style.top = Math.max( 8, Math.min( y, maxY ) ) + 'px';
		}

		function hide() {
			tip.style.opacity = '0';
		}

		return { show: show, hide: hide };
	}

	/**
	 * نمودار دونات توزیع سگمنت‌ها؛ کمان‌های SVG با انیمیشن بازشدن پلکانی.
	 */
	function renderDonut( host, items, legend ) {
		var total = 0;
		var i;

		for ( i = 0; i < items.length; i++ ) {
			total += items[ i ].count;
		}

		if ( ! items.length || total <= 0 ) {
			host.textContent = i18n( 'no_data' );
			return;
		}

		var size = 220;
		var stroke = 26;
		var radius = ( size - stroke ) / 2;
		var center = size / 2;
		var circumference = 2 * Math.PI * radius;

		var svg = svgEl( 'svg', {
			viewBox: '0 0 ' + size + ' ' + size,
			'class': 'moshtaryar-donut-svg'
		} );

		// شروع رسم از بالای دایره با پیمایش ساعتگرد.
		var group = svgEl( 'g', { transform: 'rotate( -90 ' + center + ' ' + center + ' )' } );

		var arcs = [];
		var consumed = 0;

		items.forEach( function( item ) {
			if ( item.count <= 0 ) {
				return;
			}

			var arcLength = ( item.count / total ) * circumference;

			var arc = svgEl( 'circle', {
				cx: center,
				cy: center,
				r: radius,
				fill: 'none',
				stroke: item.color,
				'stroke-width': stroke,
				'stroke-dasharray': '0 ' + circumference,
				'stroke-dashoffset': String( -consumed * circumference )
			} );
			arc.dataset.finalDash = arcLength + ' ' + ( circumference - arcLength );

			group.appendChild( arc );
			arcs.push( arc );
			consumed += item.count / total;
		} );

		svg.appendChild( group );
		host.appendChild( svg );

		// انیمیشن ملایم ورود: بازشدن هر کمان با تأخیر پلکانی.
		if ( reduceMotion ) {
			arcs.forEach( function( arc ) {
				arc.setAttribute( 'stroke-dasharray', arc.dataset.finalDash );
			} );
		} else {
			nextFrame( function() {
				arcs.forEach( function( arc, index ) {
					arc.style.transitionDelay = ( index * 90 ) + 'ms';
					arc.setAttribute( 'stroke-dasharray', arc.dataset.finalDash );
				} );
			} );
		}

		// هم‌نفس‌شدن کمان و لجند با هاور.
		if ( ! legend ) {
			return;
		}

		var rows = legend.querySelectorAll( 'li' );

		function setActive( index ) {
			var j;

			for ( j = 0; j < arcs.length; j++ ) {
				arcs[ j ].classList.toggle( 'is-dim', index !== null && j !== index );
			}
			for ( j = 0; j < rows.length; j++ ) {
				rows[ j ].classList.toggle( 'is-active', index !== null && j === index );
			}
		}

		arcs.forEach( function( arc, index ) {
			arc.addEventListener( 'mouseenter', function() {
				setActive( index );
			} );
			arc.addEventListener( 'mouseleave', function() {
				setActive( null );
			} );
		} );

		for ( i = 0; i < rows.length; i++ ) {
			( function( idx ) {
				rows[ idx ].addEventListener( 'mouseenter', function() {
					setActive( idx );
				} );
				rows[ idx ].addEventListener( 'mouseleave', function() {
					setActive( null );
				} );
			}( i ) );
		}
	}

	/**
	 * نمودار میله‌ای روند ۱۲ ماه؛ قدیمی‌ترین ماه سمت راست (خوانش طبیعی راست‌چین).
	 */
	function renderBars( host, rows, axis ) {
		var sum = 0;
		for ( var i = 0; i < rows.length; i++ ) {
			sum += Number( rows[ i ].total ) || 0;
		}

		if ( ! rows.length || sum <= 0 ) {
			host.textContent = i18n( 'no_sales' );
			return;
		}

		var width = 640;
		var height = 300;
		var pad = { top: 14, right: 48, left: 8, bottom: 34 };
		var plotWidth = width - pad.left - pad.right;
		var plotHeight = height - pad.top - pad.bottom;
		var max = ( axis && Number( axis.max ) > 0 ) ? Number( axis.max ) : 1;
		var slot = plotWidth / rows.length;
		var barWidth = Math.min( 28, slot * 0.6 );
		var baseY = pad.top + plotHeight;

		var svg = svgEl( 'svg', {
			viewBox: '0 0 ' + width + ' ' + height,
			'class': 'moshtaryar-bars-svg'
		} );

		// خطوط راهنما + برچسب مقادیر در سمت راست (چیدمان راست‌چین).
		var labels = ( axis && axis.labels ) ? axis.labels : [];

		for ( var g = 0; g <= 4; g++ ) {
			var gridY = baseY - plotHeight * g / 4;

			svg.appendChild( svgEl( 'line', {
				x1: pad.left,
				y1: gridY,
				x2: width - pad.right,
				y2: gridY,
				'class': 0 === g ? 'moshtaryar-grid moshtaryar-grid--base' : 'moshtaryar-grid'
			} ) );

			if ( g > 0 && labels[ g - 1 ] ) {
				// با direction:rtl و لنگر start، متن از این نقطه به چپ گسترش می‌یابد.
				var valueLabel = svgEl( 'text', {
					x: width - 6,
					y: gridY + 3,
					'text-anchor': 'start',
					'class': 'moshtaryar-grid-label'
				} );
				valueLabel.textContent = labels[ g - 1 ];
				svg.appendChild( valueLabel );
			}
		}

		var tooltip = makeTooltip( host );
		var bars = [];

		rows.forEach( function( row, index ) {
			// ماه‌ها از قدیم به جدید می‌آیند؛ از راست به چپ جاگذاری می‌شوند.
			var fromLeft = rows.length - 1 - index;
			var slotX = pad.left + fromLeft * slot;
			var centerX = slotX + slot / 2;

			var slotGroup = svgEl( 'g', { 'class': 'moshtaryar-slot' } );

			if ( row.total > 0 ) {
				var barHeight = Math.max( 2, Math.round( ( row.total / max ) * plotHeight ) );
				var bar = svgEl( 'rect', {
					x: centerX - barWidth / 2,
					y: baseY - barHeight,
					width: barWidth,
					height: barHeight,
					rx: 3,
					'class': 'moshtaryar-bar'
				} );
				slotGroup.appendChild( bar );
				bars.push( bar );
			}

			// ناحیهٔ نامرئی هاور برای تولتیپ، حتی برای ماه‌های بدون فروش.
			var hit = svgEl( 'rect', {
				x: slotX,
				y: pad.top,
				width: slot,
				height: plotHeight,
				fill: 'transparent',
				'class': 'moshtaryar-slot__hit'
			} );
			var tipText = row.total > 0
				? row.label + ' — ' + ( row.price || '' )
				: row.label + ' — ' + i18n( 'zero' );

			hit.addEventListener( 'mouseenter', function( event ) {
				tooltip.show( tipText, event.clientX, event.clientY );
			} );
			hit.addEventListener( 'mousemove', function( event ) {
				tooltip.show( tipText, event.clientX, event.clientY );
			} );
			hit.addEventListener( 'mouseleave', function() {
				tooltip.hide();
			} );

			slotGroup.appendChild( hit );

			var monthLabel = svgEl( 'text', {
				x: centerX,
				y: height - 12,
				'text-anchor': 'middle',
				'class': 'moshtaryar-month-label'
			} );
			monthLabel.textContent = row.label;
			slotGroup.appendChild( monthLabel );

			svg.appendChild( slotGroup );
		} );

		host.appendChild( svg );

		// انیمیشن ملایم ورود: رشد میله‌ها از پایین با تأخیر پلکانی.
		if ( ! reduceMotion && bars.length ) {
			bars.forEach( function( bar ) {
				bar.classList.add( 'is-hidden' );
			} );
			nextFrame( function() {
				bars.forEach( function( bar, index ) {
					bar.style.transitionDelay = ( index * 45 ) + 'ms';
					bar.classList.remove( 'is-hidden' );
				} );
			} );
		}
	}

	/**
	 * راه‌اندازی.
	 */
	function init() {
		bindRecalcConfirm();

		var donutHost = document.getElementById( 'moshtaryar-donut' );
		if ( donutHost && chartData.segments ) {
			renderDonut( donutHost, chartData.segments, document.getElementById( 'moshtaryar-legend' ) );
		}

		var barsHost = document.getElementById( 'moshtaryar-bars' );
		if ( barsHost && chartData.monthly ) {
			renderBars( barsHost, chartData.monthly, chartData.axis );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
