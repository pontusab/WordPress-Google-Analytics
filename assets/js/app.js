jQuery.noConflict();

jQuery( function($) {

    // Need to evaulate as wp_localize_script sends things as a string
    stats = eval('[' + data.stats + ']');

    var dayStats = [];

    var plot_options = {
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

    // Fire the graph
    $.plot( $('.stats'), [stats], plot_options );


    function showTooltip( x, y, contents ) {
        $('<div class="tooltip">' + contents + '</div>').css( {
            top: y - 75,
            left: x - 80
        }).appendTo('body').fadeIn(200);
    }

    var previousPoint = null;

    $('.stats').bind('plothover', function( event, pos, item ) {

        $('#x').text( pos.x.toFixed(2) );
        $('#y').text( pos.y.toFixed(2) );

        if ( item ) {
            if (previousPoint != item.datapoint) {
                previousPoint = item.datapoint;

                var date = moment(item.datapoint[0]);

                $('.tooltip').remove();

                var x = item.datapoint[0].toFixed(2),
                    y = item.datapoint[1].toFixed(2),

                // Set default content for tooltip
                content = '<span> ' + date.format('D MMM') + '</span><span><strong>' + item.datapoint[1] + '</strong> '+ data.page_views +'</span>';
            
                // If there is a cached item object at this index use it instead
                if( dayStats[item.dataIndex] )
                    content = dayStats[item.dataIndex].alternateText;

                // Now show tooltip
                showTooltip( item.pageX, item.pageY, content );
            }
        }
        else {
            $('.tooltip').remove();
            previousPoint = null;
        }

    });
});