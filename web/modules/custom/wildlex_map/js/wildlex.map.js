(function($, Drupal, drupalSettings) {
  Drupal.behaviors.willex_map = {
    attach: function(context, settings) {
      $('#wildlex_map').once('willex_map').each(function() {
        function Zoom(args) {
          $.extend(this, {
            $buttons: $(".zoom-button"),
            $info: $("#zoom-info"),
            scale: {
              max: 50,
              currentShift: 0
            },
            $container: args.$container,
            datamap: args.datamap
          });
          this.init();
        }

        Zoom.prototype.init = function() {
          var paths = this.datamap.svg.selectAll("path"),
            subunits = this.datamap.svg.selectAll(".datamaps-subunit");
          // preserve stroke thickness
          paths.style("vector-effect", "non-scaling-stroke");
          // disable click on drag end
          subunits.call(
            d3.behavior.drag().on("dragend", function() {
              d3.event.sourceEvent.stopPropagation();
            })
          );
          this.scale.set = this._getScalesArray();
          this.d3Zoom = d3.behavior.zoom().scaleExtent([1, this.scale.max]);
          this._displayPercentage(1);
          this.listen();
        };
        Zoom.prototype.listen = function() {
          this.$buttons.off("click").on("click", this._handleClick.bind(this));
          this.datamap.svg
            .call(this.d3Zoom.on("zoom", this._handleScroll.bind(this)))
            .on("dblclick.zoom", null); // disable zoom on double-click
        };
        Zoom.prototype.reset = function() {
          this._shift("reset");
        };
        Zoom.prototype._handleScroll = function() {
          var translate = d3.event.translate,
            scale = d3.event.scale,
            limited = this._bound(translate, scale);
          this.scrolled = true;
          this._update(limited.translate, limited.scale);
        };
        Zoom.prototype._handleClick = function(event) {
          var direction = $(event.target).data("zoom");
          this._shift(direction);
          return false;
        };
        Zoom.prototype._shift = function(direction) {
          var center = [this.$container.width() / 2, this.$container.height() / 2],
            translate = this.d3Zoom.translate(),
            translate0 = [],
            l = [],
            view = {
              x: translate[0],
              y: translate[1],
              k: this.d3Zoom.scale()
            },
            bounded;
          translate0 = [
            (center[0] - view.x) / view.k,
            (center[1] - view.y) / view.k
          ];
          if (direction == "reset") {
            view.k = 1;
            this.scrolled = true;
          } else {
            view.k = this._getNextScale(direction);
          }
          l = [translate0[0] * view.k + view.x, translate0[1] * view.k + view.y];
          view.x += center[0] - l[0];
          view.y += center[1] - l[1];
          bounded = this._bound([view.x, view.y], view.k);
          this._animate(bounded.translate, bounded.scale);
        };
        Zoom.prototype._bound = function(translate, scale) {
          var width = this.$container.width(),
            height = this.$container.height();
          translate[0] = Math.min(
            (width / height) * (scale - 1),
            Math.max(width * (1 - scale), translate[0])
          );
          translate[1] = Math.min(0, Math.max(height * (1 - scale), translate[1]));
          return {
            translate: translate,
            scale: scale
          };
        };
        Zoom.prototype._update = function(translate, scale) {
          this.d3Zoom
            .translate(translate)
            .scale(scale);
          this.datamap.svg.selectAll("g")
            .attr("transform", "translate(" + translate + ")scale(" + scale + ")");
          this._displayPercentage(scale);
        };
        Zoom.prototype._animate = function(translate, scale) {
          var _this = this,
            d3Zoom = this.d3Zoom;
          d3.transition().duration(350).tween("zoom", function() {
            var iTranslate = d3.interpolate(d3Zoom.translate(), translate),
              iScale = d3.interpolate(d3Zoom.scale(), scale);
            return function(t) {
              _this._update(iTranslate(t), iScale(t));
            };
          });
        };
        Zoom.prototype._displayPercentage = function(scale) {
          var value;
          value = Math.round(Math.log(scale) / Math.log(this.scale.max) * 100);
          this.$info.text(value + "%");
        };
        Zoom.prototype._getScalesArray = function() {
          var array = [],
            scaleMaxLog = Math.log(this.scale.max);
          for (var i = 0; i <= 10; i++) {
            array.push(Math.pow(Math.E, 0.1 * i * scaleMaxLog));
          }
          return array;
        };
        Zoom.prototype._getNextScale = function(direction) {
          var scaleSet = this.scale.set,
            currentScale = this.d3Zoom.scale(),
            lastShift = scaleSet.length - 1,
            shift, temp = [];
          if (this.scrolled) {
            for (shift = 0; shift <= lastShift; shift++) {
              temp.push(Math.abs(scaleSet[shift] - currentScale));
            }
            shift = temp.indexOf(Math.min.apply(null, temp));
            if (currentScale >= scaleSet[shift] && shift < lastShift) {
              shift++;
            }
            if (direction == "out" && shift > 0) {
              shift--;
            }
            this.scrolled = false;
          } else {
            shift = this.scale.currentShift;
            if (direction == "out") {
              shift > 0 && shift--;
            } else {
              shift < lastShift && shift++;
            }
          }
          this.scale.currentShift = shift;
          return scaleSet[shift];
        };

        var series = drupalSettings.series;
        var dataset = {};
        var onlyValues = series.map(function(obj) {
          return obj[1];
        });
        var minValue = Math.min.apply(null, onlyValues),
          maxValue = Math.max.apply(null, onlyValues);
        if(minValue == maxValue){
          minValue = 0;
        }
        var paletteScale = d3.scale.linear()
          .domain([minValue, maxValue])
          .range(["#f2e0d0", "#e67e22"]); // color
        series.forEach(function(item) {
          var iso = item[0],
            value = item[1],
            country_id = item[2];
          dataset[iso] = {
            numberOfThings: value,
            fillColor: paletteScale(value),
            countryId: country_id
          };
        });

        function Datamap() {
          this.$container = $("#wildlex_map");
          this.instance = new Datamaps({
            height: 650, //if not null, datamaps will grab the height of 'element'
            width: 850, //if not null, datamaps will grab the width of 'element'
            responsive: false,
            element: this.$container.get(0),
            fills: {
              defaultFill: '#f8efe7'
            },
            data: dataset,
            geographyConfig: {
              borderColor: '#DEDEDE',
              highlightBorderWidth: 1,
              highlightFillColor: function(geo) {
                return geo['fillColor'] || '#f8efe7';
              },
              highlightBorderColor: '#B7B7B7',
              popupTemplate: function(geo, data) {
                // don't show tooltip if country don't present in dataset
                if (!data) {
                  // tooltip content
                  return ['<div class="hoverinfo">',
                    '<strong>', geo.properties.name, '</strong>',
                    '<br>', Drupal.t('No'), ' ', drupalSettings.content_type, ' ',Drupal.t('matching the search criteria'),
                    '</div>'
                  ].join('');

                }
                // tooltip content
                return ['<div class="hoverinfo">',
                  '<strong>', geo.properties.name, '</strong>',
                  '<br>', drupalSettings.content_type, ': <strong>', data.numberOfThings, '</strong>',
                  '</div>'
                ].join('');
              }
            },
            projection: 'mercator',
            // setProjection: function(element) {
            //   var projection = d3.geo.mercator()
            //     .translate([element.offsetWidth / 2, element.offsetHeight / 2 + 100]);
            //   var path = d3.geo.path().projection(projection);
            //   return {
            //     path: path,
            //     projection: projection
            //   };
            // },
            done: this._handleMapReady.bind(this)
          });
        }

        Datamap.prototype._handleMapReady = function(datamap) {
          this.zoom = new Zoom({
            $container: this.$container,
            datamap: datamap
          });
          datamap.svg.selectAll('.datamaps-subunit').on('click', function(geography) {
            if (typeof dataset[geography.properties.iso] != "undefined") {
              var obj = dataset[geography.properties.iso];
              var url = drupalSettings.search_base_url + "f[0]=countries%3A" + obj.countryId;
              $(location).attr("href", url);
            }
            return false;
          });
        }

        var map = new Datamap();


        var w = 250, h = 50;

        var key = d3.select("#legend1")
          .append("svg")
          .attr("width", w)
          .attr("height", h);

        var legend = key.append("defs")
          .append("svg:linearGradient")
          .attr("id", "gradient")
          .attr("x1", "0%")
          .attr("y1", "100%")
          .attr("x2", "100%")
          .attr("y2", "100%")
          .attr("spreadMethod", "pad");

        legend.append("stop")
          .attr("offset", "0%")
          .attr("stop-color", "#f2e0d1")
          .attr("stop-opacity", 1);

        legend.append("stop")
          .attr("offset", "33%")
          .attr("stop-color", "#ecb07c")
          .attr("stop-opacity", 1);

        legend.append("stop")
          .attr("offset", "66%")
          .attr("stop-color", "#e68026")
          .attr("stop-opacity", 1);

        legend.append("stop")
          .attr("offset", "100%")
          .attr("stop-color", "#e15300")
          .attr("stop-opacity", 1);

        key.append("rect")
          .attr("width", w)
          .attr("height", h - 30)
          .style("fill", "url(#gradient)")
          .attr("transform", "translate(0,10)");

        var y = d3.scale.linear()
          .range([250, 0])
          .domain([maxValue, 0]);

        /*var yAxis = d3.axisBottom()
          .scale(y)
          .ticks(5);*/
        var yAxis = d3.svg.axis().scale(y).ticks(5);


        key.append("g")
          .attr("class", "y axis")
          .attr("transform", "translate(0,30)")
          .call(yAxis)
          .append("text")
          .attr("transform", "rotate(-90)")
          .attr("y", 0)
          .attr("dy", ".71em")
          .style("text-anchor", "end");
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
