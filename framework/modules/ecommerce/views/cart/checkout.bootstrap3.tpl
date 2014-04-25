{*
 * Copyright (c) 2004-2014 OIC Group, Inc.
 *
 * This file is part of Exponent
 *
 * Exponent is free software; you can redistribute
 * it and/or modify it under the terms of the GNU
 * General Public License as published by the Free
 * Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * GPL: http://www.gnu.org/licenses/gpl.txt
 *
 *}

{css unique="cart" link="`$asset_path`css/cart.css" corecss="button,panels"}

{/css}
{uniqueid assign="id"}
{messagequeue}

<div id="expresscheckout" class="cart checkout exp-skin yui3-skin-sam">
    <h1>{$moduletitle|default:"Express Checkout"|gettext}</h1>

    {if ecomconfig::getConfig('policy')!=""}
        <a href="#" id="review-policy">{"Review Store Policies"|gettext}</a>
        <div id="storepolicies" style="z-index:9999">
            <div class="yui3-widget-hd">
                {"Store Policies"|gettext}
            </div>
            <div class="yui3-widget-bd" style="overflow-y:scroll">
                {ecomconfig var='policy' default=""}
            </div>
        </div>
        {script unique="policypop" yui3mods=1}
            {literal}
            YUI(EXPONENT.YUI3_CONFIG).use('panel', 'dd-plugin', function(Y) {
                var policies = new Y.Panel({
                    srcNode : '#storepolicies',
                    headerContent: '{/literal}{"Store Policies"|gettext}{literal}',
                    width:"400px",
                    height:"350px",
                    centered:true,
                    modal:true,
                    visible:false,
                    zIndex:999,
                    constrain:true,
//                    close:true,
                    render:true,
                });
                policies.plug(Y.Plugin.Drag, {
                    handles: ['.yui3-widget-hd']
                });
                var showpanel = function(e){
                    policies.show();
                };
                Y.one("#review-policy").on('click',showpanel);
            });
            {/literal}
        {/script}
    {/if}

    {* if $order->forced_shipping == true}
    <ul id="forcedshipping" class="queue error">
       <li>{$order->product_forcing_shipping->title} requires you to ship this order via {$shipping->shippingmethod->option_title}</li>
    </ul>
    {/if *}

    <div class="totals">
        <div class="details">
            {"Subtotal"|gettext}{if $discounts} {"with discounts"|gettext}{/if}: <span class="carttotal">{$order->total|currency}</span>
        </div>
    </div>

    <div class="cartitems separate">

        <!-- p>You have <strong>{$order->item_count}</strong> item{if $order->item_count > 1}s{/if} in your cart. <a id="expandcart" href="#" class="exp-ecom-link">[Click here to show your cart]<span></span></a></p -->
        <div id="shoppingcartwrapper">
            {chain controller=cart action=show view=show_cart_only}
        </div>
    </div>
    {clear}
    {if !$noactivediscounts}
        <div class="separate">
            <h2>{"Optional Promotional Code"|gettext}</h2>

            <div class="apply-codes">
                {if !$discounts}
                    <div class="input-code">
                        {form action="addDiscountToCart"}
                            {control type="text" name="coupon_code" label=" "}
                            {control type="buttongroup" submit="Apply Code"|gettext}
                        {/form}
                    </div>
                {else}
                    <div class="codes-applied">
                        {'You\'ve applied the following'|gettext} {if $discounts|@count==1}{"coupon"|gettext}{else}{$discounts|@count} {"coupons"|gettext}{/if}
                        :
                        <ul>
                            {foreach from=$discounts item=discount}
                                <li>
                                    <strong>{$discount->coupon_code}</strong>
                                    &#160;&#160;{icon class=delete action=removeDiscountFromCart record=$discount alt="Remove discount from cart."|gettext}
                                    {br}
                                    <em>{$discount->title}</em>
                                </li>
                                {if $discount->isShippingDiscount()}{$is_shipping_discount=true}{/if}
                            {/foreach}
                        </ul>
                        {if $discounts|@count==1}{'This coupon is'|gettext} {else}{'These coupons are'|gettext} {/if} {'saving you'|gettext}
                        {if $discounts[0]->isCartDiscount()}{$order->total_discounts|currency}.
                            {else} {$order->shippingDiscount|currency}.
                        {/if}
                    </div>
                {/if}
                {clear}
            </div>
        </div>
    {/if}

    <div id="billingadshippinginfo">
        {if $order->shipping_required == true}
            <div class="shippingdetails separate">
                {if $shipping->selectable_calculators|@count > 1}
                {/if}

                {if $shipping->shippingmethod->option!=""}
                    {foreach from=$shipping->pricelist item=option}
                        {if $option.id == $shipping->shippingmethod->option}{$shpMthdOp=$option}{/if}
                    {/foreach}
                {else}
                    {foreach name="gtfoi" from=$shipping->pricelist item=option}
                        {if $smarty.foreach.gtfoi.first}{$shpMthdOp=$option}{/if}
                    {/foreach}
                {/if}
                <div class="shipping-info">
                    <h2>{"Your Shipping Information"|gettext}</h2>
                    {if $order->forced_shipping == true || $is_shipping_discount == true}
                        <ul id="forcedshipping" class="queue message">
                            {if $order->forced_shipping == true}
                                <li>{$order->forcing_shipping_reason} {"requires you to ship this order via"|gettext} {$shipping->shippingmethod->option_title}</li>
                            {/if}
                            {if $is_shipping_discount}
                                <li>{"Your full shipping discount will be reflected on the following order confirmation page, prior to submitting your order."|gettext}</li>
                            {/if}
                        </ul>
                    {/if}

                    {*if $order->orderitem|@count>1 && $shipping->splitshipping == false && $order->forced_shipping == false}
                        <a id="miltiaddresslink" class="exp-ecom-link {if hideMultiShip == 1}hide{/if}" href="{link action=splitShipping}">Ship to multiple addresses</a>
                    {/if*}

                    {if $shipping->selectable_calculators|@count > 1}{$multicalc=1}{/if}
                    {if !$shipping->address->id}{$noShippingPrices=1}{/if}

                    {if $multicalc}
                        <h3>{"Available Shipping Methods"|gettext}</h3>

                        <div class="separate">
                            {foreach key=key from=$shipping->selectable_calculators item=calc}
                                {if $shipping->calculator->id!=$key}
                                    <a rel="{$key}"
                                       href="{link shippingcalculator_id=$key controller=shipping action=selectShippingCalculator}"
                                       class="servopt">
                                        {$calc}
                                    </a>
                                    {else}
                                    <span class="servopt">{$calc}</span>
                                {/if}
                            {/foreach}
                        </div>

                        {if $order->forced_shipping == true}
                            <p>{"Your order requires"|gettext} <strong>{$shipping->shippingmethod->option_title}</strong></p>
                        {else}
                        {*
                        <p{if $noShippingPrices} class="hide"{/if}><strong id="cur-calc">{if $shipping->calculator->id}{$shipping->calculator->title}{else}{'No service selected'|gettext}{/if}</strong>  -  <a href="#" id="servicepicker">{'Select a Service'|gettext}</a></p>
                        <div id="calculators" class="exp-dropmenu">
                            <div class="hd"><span class="type-icon"></span>{'Select a Shipping Service'|gettext}</div>
                            <div class="bd">
                                <div>
                                    <ul>
                                    {foreach key=key from=$shipping->selectable_calculators item=calc}
                                        <li><a rel="{$key}" href="#" class="servopt">{$calc}</a></li>
                                    {/foreach}
                                    </ul>
                                    {form name=SelShpCal controller=shipping action=selectShippingCalculator}
                                        {control type=hidden name=shippingcalculator_id id=shipcalc_id value=$shipping->calculator->id}
                                    {/form}
                                </div>
                            </div>
                        </div>
                        *}
                        {/if}
                    {/if}
                    {if $shipping->splitshipping == false}

                        {clear}

                        <div id="shipping-services">
                            <h3>{"Selected Shipping Method"|gettext}</h3>
                            {exp_include file="`$smarty.const.BASE`framework/modules/ecommerce/views/shipping/renderOptions.tpl"}
                        </div>

                        <h3>{"Shipping Address"|gettext}</h3>
                        <!--p>Would you like to <a class="ordermessage" href="#" rel="{$shipping->shippingmethod->id}">add a gift message</a> to this Order?</p-->

                        <div class="shipping-address">
                            <div id="shpAddSwp">
                                {if $shipping->address->id == ''}
                                    {"No address yet"|gettext}
                                {else}
                                    {$shipping->address|address}
                                {/if}
                            </div>

                            <div class="bracket{if !$shipping->address->id} hide{/if}">
                                {*<a class="{button_style}" href="{link controller=address action=myaddressbook}"><strong><em>{"Change or Add Address"|gettext}</em></strong></a>*}
                                {icon class=adjust button=true controller=address action=myaddressbook text="Change or Add Address"|gettext}
                            </div>
                        </div>
                        {clear}
                    {else}

                        {* else, we have split shipping *}
                        <a id="miltiaddresslink" class="ecomlink-link"
                           href="{link action=splitShipping}">{"Edit Shipping Information"|gettext}</a>

                        {foreach from=$shipping->splitmethods item=method}
                            <div class="splitaddress">
                                <h4>{$order->countOrderitemsByShippingmethod($method->id)} {'items will be shipped to:'|gettext}</h4>
                                <!--a class="ordermessage {button_style}" href="#" rel="{$method->id}"><strong><em>Add a Gift Message to this Order</em></strong></a-->
                                <address>
                                    {$method->firstname} {$method->middlename} {$method->lastname}{br}
                                    {$method->address1}{br}
                                    {if $method->address2 != ""}{$method->address2}{br}{/if}
                                    {$method->city},
                                    {if $method->state == -2}
                                        {$method->non_us_state}
                                    {else}
                                        {$method->state|statename:abv}
                                    {/if}
                                    , {$method->zip}
                                    {if $method->state == -2}
                                        {br}{$method->country|countryname}
                                    {/if}
                                </address>
                            </div>
                        {/foreach}

                    {/if}
                </div>
                {* end split shipping *}
            {/if} {* end shipping required check *}
        </div>
        <div class="billingdetails separate">
            <h2>{"Your Billing Information"|gettext}</h2>
            <h3>{"Your billing address"|gettext}</h3>

            <div class="billing-address">
                <div id="bllAddSwp">
                    {if $billing->address->id == ''}
                        {"You have not selected an address yet."|gettext}
                    {else}
                        {$billing->address|address}
                    {/if}
                </div>
                <div class="bracket">
                    {*<a class="{button_style}" href="{link controller=address action=myaddressbook}"><strong><em>{"Change or Add Address"|gettext}</em></strong></a>*}
                    {icon class=adjust button=true controller=address action=myaddressbook text="Change or Add Address"|gettext}
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
        <div class="separate">
            {if $order->total}
                <h2>{"Payment Information"|gettext}</h2>
                <h3>{"Available Payment Methods"|gettext}</h3>
                <div id="cart-{$id}" class="">
                    <ul class="nav nav-tabs">
                        {foreach from=$billing->calculator_views item=cviews name=tabs}
                            <li{if $smarty.foreach.calcs.first} class="active"{/if}><a href="#tab{$smarty.foreach.tabs.iteration}" data-toggle="tab">{$billing->selectable_calculators[$cviews.id]}</a></li>
                        {/foreach}
                    </ul>
                    <div class="tab-content">
                        {foreach from=$billing->calculator_views item=cviews name=calcs}
                            <div id="tab{$smarty.foreach.calcs.iteration}" class="tab-pane fade{if $smarty.foreach.calcs.first} in active{/if}">
                                {exp_include file=$cviews.view calcid=$cviews.id}
                            </div>
                        {/foreach}
                    </div>
                </div>
                <div class="loadingdiv">{'Loading'|gettext}</div>
            {else}
                <div class="billing-method">
                    {form name="free" controller=cart action=preprocess}
                        {control type="hidden" name="billingcalculator_id" value=-1}
                        {control type="hidden" name="cash_amount" value=0}
                        <button id="continue-checkout" type="submit" class="{button_style}">{"Continue Checkout"|gettext}</button>
                    {/form}
                </div>
            {/if}
        </div>
        <!--div class="separate">
                <a class="awesome {$smarty.const.BTN_SIZE} {$smarty.const.BTN_COLOR}-dis continue" href="#" id="checkoutnow"><strong><em>Complete your checkout information to continue</em></strong></a>
            </div-->
    </div>
</div>
<!-- div id="loadingdiv" class="loadingdiv">Loading Checkout Page</div -->

{* edebug var=$order *}
{*  Kludged out while testing paypal *}
{*script unique="shoppingcartcheckout" yuimodules="animation,container,json" src="`$smarty.const.JS_RELATIVE`exp-ecomcheckout.js"}
//
{/script*}

{*{script unique="cart-`$id`" yui3mods="1"}*}
{*{literal}*}
    {*EXPONENT.YUI3_CONFIG.modules.exptabs = {*}
        {*fullpath: EXPONENT.JS_RELATIVE+'exp-tabs.js',*}
        {*requires: ['history','tabview','event-custom']*}
    {*};*}

	{*YUI(EXPONENT.YUI3_CONFIG).use('exptabs', function(Y) {*}
        {*Y.expTabs({srcNode: '#cart-{/literal}{$id}{literal}'});*}
		{*Y.one('#cart-{/literal}{$id}{literal}').removeClass('hide');*}
		{*Y.one('.loadingdiv').remove();*}
	{*});*}
{*{/literal}*}
{*{/script}*}

{*{if $order->total}*}
{*{script unique="cart-`$id`" jquery="jqueryui"}*}
{*{literal}*}
    {*$('#cart-{/literal}{$id}{literal}').tabs().next().remove();*}
{*{/literal}*}
{*{/script}*}
{*{/if}*}

{script unique="tabload" jquery=1 bootstrap="tab,transition"}
{literal}
    $('.loadingdiv').remove();
{/literal}
{/script}