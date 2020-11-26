define([
    'jquery',
    'mage/utils/wrapper',
], function($, wrapper) {
    'use strict';
    const actionUrl = 'gomage_linkswatch/entity/retrieve/';

    return function (targetModule) {
        /*let _newRenderSwatchOptions = targetModule.prototype._RenderSwatchOptions;
        _newRenderSwatchOptions = wrapper.wrap(_newRenderSwatchOptions, function (original, config, element) {
            return original();
        });*/

        function _newRenderSwatchOptions(config, controlId) {
            var self = this,
                optionConfig = this.options.jsonSwatchConfig[config.id],
                optionClass = this.options.classes.optionClass,
                sizeConfig = this.options.jsonSwatchImageSizeConfig,
                moreLimit = parseInt(this.options.numberToShow, 10),
                moreClass = this.options.classes.moreButton,
                moreText = this.options.moreButtonText,
                countAttributes = 0,
                html = '';

            if (!this.options.jsonSwatchConfig.hasOwnProperty(config.id)) {
                return '';
            }

            $.each(config.options, function (index) {
                var id,
                    type,
                    value,
                    thumb,
                    label,
                    width,
                    height,
                    attr,
                    swatchImageWidth,
                    swatchImageHeight;

                if (!optionConfig.hasOwnProperty(this.id)) {
                    return '';
                }

                // Add more button
                if (moreLimit === countAttributes++) {
                    html += '<a href="#" class="' + moreClass + '"><span>' + moreText + '</span></a>';
                }

                id = this.id;
                type = parseInt(optionConfig[id].type, 10);
                value = optionConfig[id].hasOwnProperty('value') ?
                    $('<i></i>').text(optionConfig[id].value).html() : '';
                thumb = optionConfig[id].hasOwnProperty('thumb') ? optionConfig[id].thumb : '';
                width = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.width : 110;
                height = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.height : 90;
                label = this.label ? $('<i></i>').text(this.label).html() : '';
                attr =
                    ' href="' + actionUrl + 'product/' + self._determineProductData().productId + '/attribute/' + config.id + '/value/' +  id + '"' +
                    ' id="' + controlId + '-item-' + id + '"' +
                    ' index="' + index + '"' +
                    ' aria-checked="false"' +
                    ' aria-describedby="' + controlId + '"' +
                    ' tabindex="0"' +
                    ' option-type="' + type + '"' +
                    ' option-id="' + id + '"' +
                    ' option-label="' + label + '"' +
                    ' aria-label="' + label + '"' +
                    ' option-tooltip-thumb="' + thumb + '"' +
                    ' option-tooltip-value="' + value + '"' +
                    ' role="option"' +
                    ' thumb-width="' + width + '"' +
                    ' thumb-height="' + height + '"';

                swatchImageWidth = _.has(sizeConfig, 'swatchImage') ? sizeConfig.swatchImage.width : 30;
                swatchImageHeight = _.has(sizeConfig, 'swatchImage') ? sizeConfig.swatchImage.height : 20;

                if (!this.hasOwnProperty('products') || this.products.length <= 0) {
                    attr += ' option-empty="true"';
                }

                if (type === 0) {
                    // Text
                    html += '<div class="' + optionClass + ' text" ' + attr + '>' + (value ? value : label) +
                        '</div>';
                } else if (type === 1) {
                    // Color
                    html += '<div class="' + optionClass + ' color" ' + attr +
                        ' style="background: ' + value +
                        ' no-repeat center; background-size: initial;">' + '' +
                        '</div>';
                } else if (type === 2) {
                    // Image
                    html += '<a class="' + optionClass + ' image" ' + attr +
                        ' style="background: url(' + value + ') no-repeat center; background-size: initial;width:' +
                        swatchImageWidth + 'px; height:' + swatchImageHeight + 'px">' + '' +
                        '</a>';
                } else if (type === 3) {
                    // Clear
                    html += '<div class="' + optionClass + '" ' + attr + '></div>';
                } else {
                    // Default
                    html += '<div class="' + optionClass + '" ' + attr + '>' + label + '</div>';
                }
            });

            return html;
        }

        targetModule.prototype._RenderSwatchOptions = _newRenderSwatchOptions;

        return targetModule;
    }
})
