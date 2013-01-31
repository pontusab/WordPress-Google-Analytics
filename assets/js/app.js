jQuery.noConflict();

jQuery( function($) {
    var wpgaGui = new NetR.WpgaGui($('.stats-holder'));
    wpgaGui.init();
});

NetR = typeof NetR === 'undefined' ? {} : NetR;

NetR.WpgaGui = function(el) {
    this.el = el;
}

NetR.WpgaGui.prototype = {
    init:function() {
        this.loadData();
    },
    loadData: function()
    {
        var self = this;

        jQuery.ajax({
            type : 'post',
            dataType : 'json',
            url : ajaxurl,
            data : {
                action: 'maps-get-normal-view'
            },
            beforeSend: function() {
                self.el.addClass('startup');
                self.el.append('<div class="loading"></div>');
            },
            success: function(response) {
                if( response.success ) {
                    self.el.html(response.html);
                    self.el.removeClass('startup');

                    stats = eval('[' + response.stats + ']');

                    self.wpga = new NetR.Wpga(jQuery('.stats'), null, response.translations);
                    self.wpga.plot(stats);
                    self.wpga.createToolTip();

                    self.el.find('.edit-wpga').click(jQuery.proxy(self.onEditClick, self));
                }
            }
        });
    },
    
    onEditClick: function(e)
    {
        var self = this;

        e.preventDefault();

        jQuery.ajax({
            type : 'post',
            dataType : 'json',
            url : ajaxurl,
            data : {
                action: 'maps-get-settings-view'
            },
            beforeSend: function() {
                self.el.append('<div class="loading"></div>');
            },
            success: function(response) {
                if( response.success ) {
                    self.el.html(response.html);

                    self.el.find('.edit-wpga').click(jQuery.proxy(self.onEditCancelClick, self));
                    self.el.find('input[type=submit]').click(jQuery.proxy(self.onEditSaveClick, self));
                }
            }
        });
    },
    onEditCancelClick: function(e)
    {
        e.preventDefault();
        this.loadData();
    },
    onEditSaveClick: function(e)
    {
        var self = this;

        e.preventDefault();

        var data = jQuery.extend({ action: 'maps-get-settings-save' }, self.el.find('form').serialize());

        jQuery.ajax({
            type : 'post',
            dataType : 'json',
            url : ajaxurl,
            data : data,
            beforeSend: function() {
                self.el.append('<div class="loading"></div>');
            },
            success: function(response) {
                if( response.success ) {
                    this.loadData();
                }
                else {
                    // Something went wrong (maybe validation error)
                }
            }
        });
    }
}

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