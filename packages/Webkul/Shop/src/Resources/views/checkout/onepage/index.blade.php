<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="@lang('shop::app.checkout.onepage.index.checkout')" />

    <meta name="keywords" content="@lang('shop::app.checkout.onepage.index.checkout')" />
@endPush

<x-shop::layouts :has-header="false" :has-feature="false" :has-footer="false">
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.checkout.onepage.index.checkout')
    </x-slot>

    {!! view_render_event('bagisto.shop.checkout.onepage.header.before') !!}

    <!-- Page Header -->
    <div class="flex-wrap">
        <div
            class="flex w-full justify-between border border-b border-l-0 border-r-0 border-t-0 px-[60px] py-4 max-lg:px-8 max-sm:px-4">
            <div class="flex items-center gap-x-14 max-[1180px]:gap-x-9">
                <a href="{{ route('shop.home.index') }}" class="flex min-h-[30px]" aria-label="@lang('shop::checkout.onepage.index.bagisto')">
                    <img src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                        alt="{{ config('app.name') }}" width="131" height="29">
                </a>
            </div>

            @guest('customer')
                <!-- Aqui modificamos y quitamos el profile bottom -->
                @include('shop::checkout.loginwithoutprofile')
            @endguest
        </div>
    </div>

    {!! view_render_event('bagisto.shop.checkout.onepage.header.after') !!}

    <!-- Page Content -->
    <div class="container px-[60px] max-lg:px-8 max-sm:px-4">

        {!! view_render_event('bagisto.shop.checkout.onepage.breadcrumbs.before') !!}

        <!-- Breadcrumbs -->
        @if (core()->getConfigData('general.general.breadcrumbs.shop'))
            <x-shop::breadcrumbs name="checkout" />
        @endif

        {!! view_render_event('bagisto.shop.checkout.onepage.breadcrumbs.after') !!}

        <!-- Checkout Vue Component -->
        <v-checkout>
            <!-- Shimmer Effect -->
            <x-shop::shimmer.checkout.onepage />
        </v-checkout>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-checkout-template">
                                                <template v-if="! cart">
                                                    <!-- Shimmer Effect -->
                                                    <x-shop::shimmer.checkout.onepage />
                                                </template>

                                                <template v-else>
                                                    <div class="grid grid-cols-[1fr_auto] gap-8 max-lg:grid-cols-[1fr] max-md:gap-5">
                                                        <!-- Included Checkout Summary Blade File For Mobile view -->
                                                        <div class="hidden max-md:block">
                                                            @include('shop::checkout.onepage.summary')
                                                        </div>

                                                        <div
                                                            class="overflow-y-auto max-md:grid max-md:gap-4"
                                                            id="steps-container"
                                                        >
                                                            <!-- Included Addresses Blade File -->
                                                            <template v-if="['address', 'shipping', 'payment', 'review'].includes(currentStep)">
                                                                @include('shop::checkout.onepage.address')
                                                            </template>

                                                            <!-- Included Shipping Methods Blade File -->
                                                            <template v-if="cart.have_stockable_items && ['shipping', 'payment', 'review'].includes(currentStep)">
                                                                @include('shop::checkout.onepage.shipping')
                                                            </template>

                                                            <!-- Included Payment Methods Blade File -->
                                                            <template v-if="['payment', 'review'].includes(currentStep)">
                                                                @include('shop::checkout.onepage.payment')
                                                            </template>
                                                        </div>

                                                        <!-- Included Checkout Summary Blade File For Desktop view -->
                                                        <div class="sticky top-8 block h-max w-[442px] max-w-full max-lg:w-auto max-lg:max-w-[442px] ltr:pl-8 max-lg:ltr:pl-0 rtl:pr-8 max-lg:rtl:pr-0">
                                                            <div class="block max-md:hidden">
                                                                @include('shop::checkout.onepage.summary')
                                                            </div>

                                                            <div
                                                                class="flex justify-end"
                                                                v-if="canPlaceOrder"
                                                            >
                                                                <template v-if="cart.payment_method == 'paypal_smart_button'">
                                                                    {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.before') !!}

                                                                    <!-- Paypal Smart Button Vue Component -->
                                                                    <v-paypal-smart-button></v-paypal-smart-button>

                                                                    {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.after') !!}
                                                                </template>



                                                                <template v-else>
                                                                    <x-shop::button
                                                                        type="button"
                                                                        class="primary-button whatsapp-button w-max rounded-2xl px-11 py-3 max-md:mb-4 max-md:w-full max-md:max-w-full max-md:rounded-lg max-sm:py-1.5"
                                                                        :title="trans('shop::app.checkout.onepage.summary.place-order-ws')"
                                                                        ::disabled="isPlacingOrder"
                                                                        ::loading="isPlacingOrder"
                                                                        @click="sendWhatsApp"
                                                                        >
                                                                    </x-shop::button>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </script>

        <script type="module">
            app.component('v-checkout', {
                template: '#v-checkout-template',

                data() {
                    return {
                        cart: null,

                        displayTax: {
                            prices: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_prices') }}",

                            subtotal: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_subtotal') }}",

                            shipping: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_shipping_amount') }}",
                        },

                        isPlacingOrder: false,

                        currentStep: 'address',

                        shippingMethods: null,

                        paymentMethods: null,

                        canPlaceOrder: false,
                    }
                },

                mounted() {
                    this.getCart();
                },

                methods: {
                    getCart() {
                        this.$axios.get("{{ route('shop.checkout.onepage.summary') }}")
                            .then(response => {
                                this.cart = response.data.data;

                                this.scrollToCurrentStep();
                            })
                            .catch(error => {});
                    },

                    stepForward(step) {
                        this.currentStep = step;

                        if (step == 'review') {
                            this.canPlaceOrder = true;

                            return;
                        }

                        this.canPlaceOrder = false;

                        if (this.currentStep == 'shipping') {
                            this.shippingMethods = null;
                        } else if (this.currentStep == 'payment') {
                            this.paymentMethods = null;
                        }
                    },

                    stepProcessed(data) {
                        if (this.currentStep == 'shipping') {
                            this.shippingMethods = data;
                        } else if (this.currentStep == 'payment') {
                            this.paymentMethods = data;
                        }

                        this.getCart();
                    },

                    scrollToCurrentStep() {
                        let container = document.getElementById('steps-container');

                        if (!container) {
                            return;
                        }

                        container.scrollIntoView({
                            behavior: 'smooth',
                            block: 'end'
                        });
                    },
                    sendWhatsApp() {
                        // 1) Guardas de estado
                        if (this?.canPlaceOrder === false) return;

                        // 2) Número de WhatsApp desde .env (inyectado en Blade via config/services.php)
                        // .env -> WHATSAPP_NUMBER=573001234567
                        // config/services.php -> 'whatsapp_number' => env('WHATSAPP_NUMBER')
                        const waRaw = @json(config('services.whatsapp_number'));
                        const waPhone = String(waRaw || '').replace(/\D/g, ''); // solo dígitos
                        if (!waPhone) {
                            console.warn('Falta WHATSAPP_NUMBER en config/services.php');
                            return;
                        }

                        // 3) Traducciones/mapeos de métodos
                        const paymentMap = {
                            'moneytransfer': 'Transferencia bancaria / Pago Móvil / Otros',
                            // agrega más si los usas...
                        };

                        const shippingMap = {
                            'flatrate_flatrate': 'Acordar con el vendedor',
                            // agrega más si los usas...
                        };

                        // 4) Items
                        const items = (this?.cart?.items ?? []).map(i => {
                            const name = i?.name ?? '';
                            const sku = i?.sku ?? '';
                            const qty = Number(i?.quantity ?? i?.qty ?? 1) || 1;
                            const price = i?.formatted_price_incl_tax ?? i?.formatted_price ?? '';
                            // Oculta SKU si viene vacío
                            const skuTxt = sku ? ` (SKU: ${sku})` : '';
                            return `${name}${skuTxt} x ${qty}${price ? ` — ${price}` : ''}`;
                        });

                        // 5) Totales
                        const total = this?.cart?.formatted_grand_total ?? '';

                        // 6) Direcciones (normaliza address1 que puede ser array)
                        const addrStr = a => {
                            if (!a) return '';
                            const a1 = Array.isArray(a.address1) ? a.address1.join(', ') : (a.address1 || '');
                            return [a1, a.address2, a.city, a.state, a.postcode, a.country]
                                .filter(Boolean)
                                .join(', ');
                        };

                        const billing = this?.cart?.billing_address || null;
                        const shipping = this?.cart?.shipping_address || null;

                        const custName = [billing?.first_name, billing?.last_name, shipping?.first_name, shipping
                                ?.last_name
                            ]
                            .filter(Boolean)
                            .slice(0, 2)
                            .join(' ');
                        const custPhone = billing?.phone || shipping?.phone || '';

                        const b = addrStr(billing);
                        const s = addrStr(shipping);
                        const hasAddr = Boolean(b || s || custName || custPhone);

                        // 7) Métodos elegidos (con mapeo amigable)
                        const rawPayMethod = this?.cart?.payment_title ||
                            this?.paymentMethods?.find(p => p.method === this?.cart?.payment_method)?.title ||
                            this?.cart?.payment_method ||
                            '';

                        const rawShipMethod = this?.cart?.selected_shipping_rate?.method_title ||
                            this?.cart?.selected_shipping_rate?.method ||
                            this?.cart?.shipping_method ||
                            '';

                        const payFriendly = paymentMap[rawPayMethod] ?? rawPayMethod;
                        const shipFriendly = shippingMap[rawShipMethod] ?? rawShipMethod;

                        // 8) Construcción del mensaje
                        const lines = [];
                        lines.push('*Hola, quiero finalizar este pedido:*');

                        if (items.length) {
                            lines.push('', '*Artículos:*');
                            items.forEach(x => lines.push(`• ${x}`));
                        }

                        if (total) {
                            lines.push('', `*Total:* ${total}`);
                        }

                        if (payFriendly || shipFriendly) {
                            lines.push('');
                            if (payFriendly) lines.push(`*Método de pago:* ${payFriendly}`);
                            if (shipFriendly) lines.push(`*Envío:* ${shipFriendly}`);
                        }

                        if (hasAddr) {
                            lines.push('');
                            if (custName) lines.push(`*Nombre:* ${custName}`);
                            if (custPhone) lines.push(`*Teléfono:* ${custPhone}`);
                            if (b) lines.push(`*Facturación:* ${b}`);
                            if (s && s !== b) lines.push(`*Envío:* ${s}`);
                        }

                        const msg = encodeURIComponent(lines.join('\n'));

                        // 9) Redirección a WhatsApp
                        const url = `https://wa.me/${waPhone}?text=${msg}`;
                        window.open(url, '_blank');
                    }


                },
            });
        </script>
    @endPushOnce
</x-shop::layouts>
