jQuery.noConflict();

jQuery( function($) {
    jQuery.ajax({
        type : 'post',
        dataType : 'json',
        url : ajaxurl,
        data : {
            action: 'maps-get-normal-view'
        },
        beforeSend: function() {
            jQuery('.stats-holder').addClass('startup');
            jQuery('.stats-holder').append('<div class="loading"></div>');
        },
        success: function(response) {
            if( response.success ) {
                jQuery('.stats-holder').html(response.html);
                jQuery('.stats-holder').removeClass('startup');

                stats = eval('[' + response.stats + ']');

                var wpga = new NetR.Wpga($('.stats'), null, response.translations);
                wpga.plot(stats);
                wpga.createToolTip();
            }
        }
    });
});

NetR = typeof NetR === 'undefined' ? {} : NetR;

NetR.Wpga = function(el, options, translations) {
    this.options = jQuery.extend({}, NetR.Wpga.defaultOptions, options || {});
    this.translations = jQuery.extend({}, NetR.Wpga.defaultTranslations, options || {});
    this.el = el;
}

NetR.Wpga.prototype = {
    plot:function(stats) {
        this.stats = stats;
        // Fire the graph
        jQuery.plot(this.el, [stats], this.options.plot );
    },

    createToolTip: function() {
        var self = this;
        self.toolTipEl = jQuery('<div class="tooltip"></div>').appendTo('body').hide();

        this.el.bind('plothover', function( event, pos, item ) {
            if ( item ) {
                if (self.previousPoint != item.dataIndex) {
                    self.previousPoint = item.dataIndex;

                    var date = moment(item.datapoint[0]);

                    self.toolTipEl.hide();

                    var x = item.datapoint[0].toFixed(2),
                        y = item.datapoint[1].toFixed(2),

                    // Set default content for tooltip
                    content = '<span> ' + date.format('D MMM') + '</span><span><strong>' + item.datapoint[1] + '</strong> '+ self.translations.page_views +'</span>';

                    // Now show tooltip
                    self.showTooltip( item.pageX, item.pageY, content );
                }
            }
            else {
                self.toolTipEl.hide();
                self.previousPoint = null;
            }

        });
    },
    showTooltip: function( x, y, content ) {
        this.toolTipEl.html(content).css( {
            top: y - 75,
            left: x - this.toolTipEl.width()/2
        }).fadeIn(200);
    }
};

NetR.Wpga.defaultTranslations = {
    page_views: 'Page views'
};

NetR.Wpga.defaultOptions = {
    plot: {
        xaxis: {
            mode: 'time',
            timeformat: "%d %b",
            tickColor: 'transparent',
            font: {
                size: 12,
                weight: "bold",
                family: "Helvetica Neue, Helvetica, Arial, sans-serif"
            },
            color: '#666'
        },
        yaxis: {
            font: {
                size: 12,
                weight: "bold",
                family: "Helvetica Neue, Helvetica, Arial, sans-serif"
            },
            color: '#666'
        },
        series: {
            bars: {
                align: 'center',
                show: true,
                fill: true,
                points: {
                       show: true,
                   },
                shadowSize: 0,
                lineWidth: 0,
                fillColor: '#8db9cc',
                barWidth: 77760000 // (24 * 60 * 60 * 1000) * 0.9
            },
            highlightColor: '#fcab00',
        },
        grid: {
            color: '#646464',
            borderColor: 'transparent',
            borderWidth: 20,
            hoverable: true,
            labelMargin: 20,
            axisMargin: 20
        }
    }
};