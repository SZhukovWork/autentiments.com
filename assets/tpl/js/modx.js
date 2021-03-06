function calcRealTotalCost() {
    var real_total_cost = 0;
    $(miniShop2.Cart.cart + ' .au-cart__card').each(function(){
        var price = $(this).find('input[name=price]').val(),
            count = $(this).find('input[name=count]').val();
        real_total_cost = real_total_cost + (price * count);
    });
    $('.real_total_cost').text(miniShop2.Utils.formatPrice(real_total_cost));
}

function chooseVisibleDelivery(delivery) {
    if ($('input[name="delivery"]:checked').parent().is(':visible') === false) {
        $('.au-ordering__delivery-row:visible').first().find('input[name="delivery"]').click();
    }
}

function declension(n, titles) {
    return titles[(n % 10 === 1 && n % 100 !== 11) ? 0 : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2];
}

function checkDeliveryFields() {
    if ($('#country').val() && $('#city').val() && $('#index').val()) {
        miniShop2.Order.getcost();
        $('.au-ordering').addClass('next-step');
    } else {
        $('.au-ordering').removeClass('next-step');
    }
}

function validateEmail(email) {
    const re = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
    return re.test(String(email).toLowerCase());
}

function msmcGetPrice(price, floor = false) {
    price = price.toString().replace(/\s+/g, '');
    
    let cource = parseFloat(msMultiCurrencyConfig.course),
        currencyId = msMultiCurrencyConfig.userCurrencyId,
        priceFloat = parseFloat(price);

    if (currencyId != 1) {
        price = priceFloat/cource;
        if (floor === true) {
            return Math.floor(price);
        } else {
            return Math.ceil(price);
        }
    } else {
        return price;
    }
}

// Сохраняем в скрытое поле сроки доставки
function setOrderRates() {
    $('input[name="order_rates"]').val($('input[name="delivery"]:checked').siblings('label').find('.delivery_rate').text())
}

// Сохраняем в скрытое поле скидку на заказ
function setOrderDiscount() {
    $('input[name="order_discount"]').val($('.ms2_total_discount_custom').text().replace(" ", ""))
}

function promocodeApplied() {
    $('.au-promo-code__submit').removeClass('active');
    $('.au-promo-code__form').addClass('applied-code');
    $('.au-ordering__bonuses').addClass('disabled-bonuses');
}

function promocodeRemoved() {
    $('.au-promo-code__submit').addClass('active');
    $('.au-promo-code__form').removeClass('applied-code');
    $('.au-ordering__bonuses').removeClass('disabled-bonuses');
}

function showLoading() {
    $('.ajax-loader').addClass('enabled');
    $('.ajax-loader-block').addClass('loading');
}

function hideLoading() {
    $('.ajax-loader').removeClass('enabled');
    $('.ajax-loader-block').removeClass('loading');
}

function selectFirstSize() {
    if ($('#msProduct label.au-product__size')) {
        $('#msProduct label.au-product__size').first().trigger('click');
    }
}

// Переключение цветов в галерее товара
function reloadMsGallery(color, product) {
    if ($('#msGallery').length) {
        $.post("/assets/components/stik/getAjaxMsGallery.php", {color: color, product: product}, function(data) {
            if (data) {
                $('#msGallery').replaceWith(data);
                productGalleryInit();
            }
        });
    }
}

// Переключение цветов в карточке товарв
function changeCardColor(color, $input) {
    let $card = $input.parents('.au-card');
    if($card.length){
        let $img = $card.find('.js_card-img');
        if ($img.length) {
            $img.addClass('fade');
            $.post("/assets/components/stik/getAjaxMsGallery.php", {color: color, product: $input.attr('data-product'), mode: 'card'}, function(data) {
                data = $.parseJSON(data);
                if (data) {
                    if (data.gallery) {
                        $img.html(data.gallery);
                    }
                    if (data.prices) {
                        $card.find('js_card-prices').html(data.prices);
                    }
                }
                $img.removeClass('fade');
            });
        } else {
            console.log('Не найден блок .js_card-img');
        }
    }

}

$(document).ready(function() {
    if (window.miniShop2) {
        
        $(document).on('mspc_set mspc_freshen', function(e, response) { // событие mspc_freshen добавлено в кастомном js-файле
            if(response.mspc.discount_amount > 0) {
                $('.mspc_discount_amount span').text(miniShop2.Utils.formatPrice(msmcGetPrice(response.mspc.discount_amount)));
                promocodeApplied();
            } else {
                $('.mspc_discount_amount span').text("-");
                promocodeRemoved();
            }
        });
    
        $(document).on('mspc_remove', function(e, response) {
            if(response.mspc.discount_amount == 0) {
                $('.mspc_discount_amount span').text("-");
                promocodeRemoved();
            }
        });
    
        miniShop2.Callbacks.add('Cart.change.response.success', 'stik', function(response) {
            $('.ms2_total_cost').text(miniShop2.Utils.formatPrice(msmcGetPrice(response.data.total_cost)));
            // $('.mse2_total_declension').text(declension(response.data.total_count, stik_declension_product_js));
            $('.ms2_total_no_discount').text(miniShop2.Utils.formatPrice(msmcGetPrice(response.data.real_total_cost)));
            $('.ms2_total_discount_custom').text(miniShop2.Utils.formatPrice(msmcGetPrice(response.data.total_discount)));
            calcRealTotalCost();
            if ($('.msOrder').length) {
                miniShop2.Order.getcost();
            }
        });
    
        miniShop2.Callbacks.add('Cart.remove.response.success', 'stik', function(response) {
            $('.ms2_total_no_discount').text(miniShop2.Utils.formatPrice(response.data.real_total_cost));
            calcRealTotalCost();
            if ($('.msOrder').length) {
                miniShop2.Order.getcost();
            }
        });
        
        miniShop2.Callbacks.add('Cart.add.response.success', 'stik', function(response) {
            showAjaxCart();
            ym(86113805,'reachGoal','add_to_cart');
        });

        miniShop2.Callbacks.add('Order.add.response.success', 'stik', function(response) {
            // если было изменено поле страна/город/индекс и все эти поля не пустые
            if(
                !miniShop2.Utils.empty(response.data.country) ||
                !miniShop2.Utils.empty(response.data.city) ||
                !miniShop2.Utils.empty(response.data.index)
            ) {
                checkDeliveryFields();
            }
            
            if (typeof(response.data.msloyalty) != "undefined" && response.data.msloyalty !== null) {
                miniShop2.Order.getcost();
                
                if (response.data.msloyalty > 0) {
                    $('.au-bonuses__form').addClass('used-bonuses');
                    $('.au-promo-code__form').addClass('disabled-code');
                    $('.mspc_field').prop('disabled', true);
                } else {
                    $('.au-bonuses__form').removeClass('used-bonuses');
                    $('.au-promo-code__form').removeClass('disabled-code');
                    $('.mspc_field').prop('disabled', false);
                }
            }
            
            chooseVisibleDelivery();
        });
        
        miniShop2.Callbacks.add('Order.getcost.before', 'stik', function(response) {
            // Перед расчетом стоимости, делаем блок с ценами, доставками, способами оплаты неактивным и показываем прелоадер
            showLoading();
        });
        
        miniShop2.Callbacks.add('Order.getcost.response.error', 'stik', function(response) {
            // убираем прелоадер
            hideLoading();
        });
        
        miniShop2.Callbacks.add('Order.getcost.response.success', 'stik', function(response) {
            // убираем прелоадер
            hideLoading();
            
            // var countryLower = $('#country').val().toLowerCase();
            
            // if (countryLower == 'россия' || countryLower == 'russian federation') {
            //     $('.delivery-ru').show();
            // } else {
            //     $('.delivery-ru').hide();
            // }
            
            // Стоимость заказа, поскольку она находится за пределами #msOrder
            $('.ms2_order_cost').text(miniShop2.Utils.formatPrice(response.data.cost));
            
            // Отключаем кнопку оформления заказа, если не рассчиталась доставка
            // if(response.data.delivery_cost > 0) {
            //     $('#order_submit').prop('disabled', false)
            //     $('#delivery_error_text').hide()
            // } else {
            //     $('#order_submit').prop('disabled', true)
            //     $('#delivery_error_text').show()
            // }
            
            // Общая стоимость доставки
            if(response.data.delivery_cost > 0) {
                $('.ms2_delivery_cost').text(miniShop2.Utils.formatPrice(response.data.delivery_cost) + " " + ms2_frontend_currency);
                // $('#city, #index').removeClass('error');
            } else {
                let is_free = $('input[name="delivery"]:checked').hasClass('free-delivery');
                $('.ms2_delivery_cost').text(is_free ? stik_order_delivery_free : stik_order_delivery_not_calculated);
                // $('#city, #index').addClass('error');
            }
            
            // бонусы
            if(response.data.msloyalty > 0) {
                $('.loyalty_discount_amount').show();
                $('.loyalty_discount_amount span').text(miniShop2.Utils.formatPrice(response.data.msloyalty));
                $('.msloyalty_writeoff_amount').text(miniShop2.Utils.formatPrice(response.data.msloyalty));
                $('.msloyalty_writeoff_declension').text(declension(response.data.msloyalty, stik_declension_bonuses_js));
            } else {
                $('.loyalty_discount_amount').hide();
                $('.msloyalty_writeoff_amount').text(0);
            }
            
            if(response.data.loyalty_accrual > 0) {
                $('.msloyalty_accrual').text(miniShop2.Utils.formatPrice(response.data.loyalty_accrual));
                $('.msloyalty_accrual_declension').text(declension(response.data.loyalty_accrual, stik_declension_bonuses_js));
            }
            
            chooseVisibleDelivery();
            setOrderRates();
            setOrderDiscount();
        });
        
        miniShop2.Callbacks.add('Order.submit.before', 'stik', function(response) {
            // показываем прелоадер
            showLoading();
        });
        
        miniShop2.Callbacks.add('Order.submit.response.success', 'stik', function(response) {
            // убираем прелоадер
            hideLoading();
            
            // const orderCost = parseFloat($('.ms2_order_cost').first().text().replace(" ", ""));
            ym(86113805,'reachGoal','do_zakaz');
            // fbq('track', 'Purchase', { currency: "RUB", value: orderCost.toFixed(2) }, {eventID: response.data.msorder});
        });
        
        miniShop2.Callbacks.add('Order.submit.response.error', 'stik', function(response) {
            // убираем прелоадер
            hideLoading();
            if (response.data.indexOf( 'point' ) != -1 ) {
                $('#cdek2_map_ajax').addClass('error');
            } else {
                $('#cdek2_map_ajax').removeClass('error');
            }
        });
        
        setTimeout(function() {
            checkDeliveryFields();
        }, 2000);
    }
    
    
    $('.au-bonuses__cancel').click(function(e) {
        e.preventDefault();
        miniShop2.Order.add('msloyalty', '');
    });
    
    $('#join_loyalty_visible').on('click', function() {
        if($(this).prop('checked')) {
            $('.msOrder #join_loyalty_order').prop('checked', true);
        } else {
            $('.msOrder #join_loyalty_order').prop('checked', false);
        }
    });
    
    // Сохраняем поле в сессию при изменениии select
    if ($('.msOrder').length) {
        $(document).on('change', '.msOrder select', function () {
            var $this = $(this);
            var key = $this.attr('name');
            var value = $this.find('option:selected').val();
            miniShop2.Order.add(key, value);
        });
    }
    
    // изменение кол-ва в ajax-корзине
    $(document).on('change', '#ms2_cart_modal input[name=count]', function () {
        if (!!$(this).val()) {
            $(this).closest('.ms2_form').submit();
        }
    });
    
    $(document).on('click', '.au-cart__minus', function () {
        var $input = $(this).siblings('span').find('input[type="number"]');
        var count = parseInt($input.val()) - 1;
        count = count < 1 ? 1 : count;
        $input.val(count);
        $input.change();
        return false;
    });
    
    $(document).on('click', '.au-cart__plus', function () {
        var $input = $(this).siblings('span').find('input[type="number"]');
        if (parseInt($input.val()) < parseInt($input.attr('max'))) {
            $input.val(parseInt($input.val()) + 1);
            $input.change();
        } else {
            // miniShop2.Message.error(stik_basket_not_enough);
        }
        return false;
    });
    
    $('.header__link-basket').click(function(){
        showAjaxCart();
    });
    
    $('#order_submit').click(function(){
        $('button#submitbtn').click();
    });
    
    // Предотвращаем показ предыдущей стоимости доставки СДЭК при неверно указанных данных
    $("#city").bind("paste keyup", function() {
        if ($('input[name="cdek_id"]').val() !== '') {
            miniShop2.Order.add('cdek_id', '');
        }
    });

    if (typeof msFavorites != 'undefined') {
        msFavorites.addMethodAction('success', 'name_action', function (r) {
            var self = this;
            if (self.data && self.data.method == 'add') {
                // dataLayer.push({'event': 'favorite'});
                // Facebook Conversions API
                // $.ajax({
                //     method: "POST",
                //     url: document.location.href,
                //     data: { fb_conversions: "favorites" }
                // });
            }
        });
    }
});

// переключение цвета на странице товара
$('#msProduct input.au-product__color-input').on('change', function () {
    let id = $('#msProduct .ms2_form input[name=id]').val();
    let $this = this;
    $.post(window.location.href, {
        stikpr_action: 'sizes/get',
        language: $('html').attr('lang'),
        product_id: id,
        selected_color: $(this).val()
        
    }, function(data) {
        if (data) {
            $('#ajax_sizes').html(data);
            selectFirstSize();
        }
    });
    $('.au-product__add-entrance').removeClass('active');
    $('.au-product__add-entrance').removeClass('end').prop('disabled', false);
    reloadMsGallery($($this).val(), $($this).data('product'));
});

$(document).ready(function() {
    selectFirstSize();
});

// цена оффера
$(document).on('click', '#msProduct label.au-product__size', function () {
    let id = $('#msProduct .ms2_form input[name=id]').val(),
        color = $('input[name="options[color]"]:checked').val(),
        size = $(this).siblings('input').val();
    $.post(window.location.href, {
        stikpr_action: 'price/get',
        language: $('html').attr('lang'),
        product_id: id,
        selected_color: color,
        selected_size: size
        
    }, function(data) {
        data = JSON.parse(data);
        if (data) {
            $('.js_card_price span').html(miniShop2.Utils.formatPrice(msmcGetPrice(data.price)));
            if (data.old_price > 0) {
                $('.js_card_old_price').show();
                $('.js_card_old_price span').html(miniShop2.Utils.formatPrice(msmcGetPrice(data.old_price)));
            } else {
                $('.js_card_old_price').hide();
            }
        }
    });
});

// Переключение цвета в карточке товара
$(document).on('change', 'input.au-card__color-input', function () {
    let $this = $(this),
        color = $($this).val();
    changeCardColor(color, $this);
    $($this).parents('.au-card').find('a').each(function(){
        let href = $(this).attr('href').split('?')[0];
        $(this).attr('href', href + '?color=' + color)
    });
});

$(document).on('af_complete', function(event, response) {
    if (response.success) {
        var form = response.form;
    
        switch (form.attr('id')) {
            case 'contacts_form':
                $('.au-contacts__form-box').addClass('hide');
                $('.au-contacts__message-info').addClass('show');
                // dataLayer.push({'event': 'message'});
                break;
            case 'newsletter_subscribe_form':
            case 'greeting_subscribe_form':
                $('.au-subscribe').addClass('subscribe_submit-end');
                // dataLayer.push({'event': 'email'});
                break;
            case 'welcome_subscribe_form':
                $('.au-welcome__col').addClass('welcome_submit-end');
                break;
            case 'size_subscribe_form':
                $('.au-close').trigger('click');
                $('.au-product__add-entrance').addClass('end').prop('disabled', true);
                break;
            case 'join_loyalty':
                $('.au-ordering__loyalty_start').removeClass('active');
                $('.au-ordering__loyalty_end').addClass('active');
                break;
            case 'join_loyalty_profile':
                location.reload();
                // $('.au-profile__loyalty').removeClass('active');
                // $('.au-profile__loyalty_bonuses').addClass('active');
                break;
        }
    }
});
