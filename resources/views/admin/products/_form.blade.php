@php
    $isEdit = $product->exists;
    $tabs = [
        'general' => 'General',
        'variants' => 'Variants & Pricing',
        'inventory' => 'Inventory',
        'images' => 'Images',
        'organization' => 'Organization',
        'seo' => 'SEO',
    ];
    // Shaped for the JS variant repeater (public/js/shop-admin.js). Money is
    // pre-formatted to the plain string the controller expects back
    // (ProductController::syncVariants parses it via Money::parse()).
    $initialVariants = $product->variants->map(fn ($v) => [
        'id' => $v->id,
        'option1_name' => $v->option1_name, 'option1_value' => $v->option1_value,
        'option2_name' => $v->option2_name, 'option2_value' => $v->option2_value,
        'option3_name' => $v->option3_name, 'option3_value' => $v->option3_value,
        'sku' => $v->sku, 'barcode' => $v->barcode,
        'price' => $v->price_input,
        'compare_at_price' => $v->compare_at_input,
        'cost' => $v->cost_input,
        'inventory_qty' => $v->inventory_qty,
        'weight_grams' => $v->weight_grams,
        'track_inventory' => $v->track_inventory,
    ])->values();
@endphp
<div x-data="{ tab: 'general' }">
    <div class="inline-flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1 mb-6" role="tablist" aria-label="Product sections">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" :aria-selected="(tab === '{{ $key }}').toString()" @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Name/variants/organization/seo all save together through this one form. --}}
    <form method="POST" action="{{ $isEdit ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        {{-- General --}}
        <div x-show="tab === 'general'" x-cloak class="space-y-6">
            <x-card title="General" subtitle="The basics shoppers see first.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Name" for="name" required :error="$errors->first('name')" class="sm:col-span-2">
                        <x-input id="name" name="name" data-slug-source="#slug" :value="old('name', $product->name)" required autofocus />
                    </x-field>
                    <x-field label="Slug" for="slug" hint="Blank auto-generates from the name." :error="$errors->first('slug')">
                        <x-input id="slug" name="slug" :value="old('slug', $product->slug)" placeholder="auto-generated" />
                    </x-field>
                    <x-field label="Status" for="status" required :error="$errors->first('status')">
                        <x-select id="status" name="status">
                            @foreach (\App\Models\Product::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $product->status) === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Vendor" for="vendor" :error="$errors->first('vendor')">
                        <x-input id="vendor" name="vendor" :value="old('vendor', $product->vendor)" />
                    </x-field>
                    <x-field label="Product Type" for="product_type" :error="$errors->first('product_type')">
                        <x-input id="product_type" name="product_type" :value="old('product_type', $product->product_type)" />
                    </x-field>
                    <x-field label="Tax Class" for="tax_class" hint="Matches a Tax Rule's tax class." :error="$errors->first('tax_class')" class="sm:col-span-2">
                        <x-input id="tax_class" name="tax_class" :value="old('tax_class', $product->tax_class ?? 'standard')" placeholder="standard" />
                    </x-field>
                    <x-field label="Excerpt" for="excerpt" hint="Short summary shown in listings." :error="$errors->first('excerpt')" class="sm:col-span-2">
                        <x-input id="excerpt" name="excerpt" :value="old('excerpt', $product->excerpt)" maxlength="500" />
                    </x-field>
                    <x-field label="Description" for="description" :error="$errors->first('description')" class="sm:col-span-2">
                        <textarea id="description" name="description" rows="6"
                            class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('description', $product->description) }}</textarea>
                    </x-field>
                </div>
                <div class="mt-5 border-t border-slate-100 pt-5">
                    <x-toggle name="is_featured" :checked="old('is_featured', $product->is_featured)"
                        label="Featured Product" description="Featured products can be highlighted on the storefront home page." />
                </div>
            </x-card>
        </div>

        {{-- Variants & Pricing --}}
        <div x-show="tab === 'variants'" x-cloak>
            <x-card title="Variants & Pricing" subtitle="Every product has at least one variant — it carries the price, SKU, and stock.">
                <div x-data="variantRepeater(@js($initialVariants))">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="rounded-lg ring-1 ring-slate-200 p-4 mb-4 last:mb-0 bg-slate-50/50">
                            <input type="hidden" :name="`variants[${index}][id]`" :value="row.id">
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <h4 class="text-sm font-semibold text-slate-900" x-text="rowLabel(row, index)"></h4>
                                <button type="button" x-show="rows.length > 1" @click="removeRow(index)"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-rose-600 hover:text-rose-700">
                                    <x-icon name="trash" class="w-3.5 h-3.5" /> Remove
                                </button>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Option 1 Name</label>
                                    <input type="text" :name="`variants[${index}][option1_name]`" x-model="row.option1_name" placeholder="Size"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Option 1 Value</label>
                                    <input type="text" :name="`variants[${index}][option1_value]`" x-model="row.option1_value" placeholder="Medium"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Option 2 Name</label>
                                    <input type="text" :name="`variants[${index}][option2_name]`" x-model="row.option2_name" placeholder="Color"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Option 2 Value</label>
                                    <input type="text" :name="`variants[${index}][option2_value]`" x-model="row.option2_value" placeholder="Charcoal"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Option 3 Name</label>
                                    <input type="text" :name="`variants[${index}][option3_name]`" x-model="row.option3_name"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Option 3 Value</label>
                                    <input type="text" :name="`variants[${index}][option3_value]`" x-model="row.option3_value"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">SKU</label>
                                    <input type="text" :name="`variants[${index}][sku]`" x-model="row.sku"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Barcode</label>
                                    <input type="text" :name="`variants[${index}][barcode]`" x-model="row.barcode"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Weight (Grams)</label>
                                    <input type="number" min="0" :name="`variants[${index}][weight_grams]`" x-model="row.weight_grams"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Opening Inventory</label>
                                    <input type="number" :name="`variants[${index}][inventory_qty]`" x-model="row.inventory_qty"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 items-end">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Price</label>
                                    <input type="text" inputmode="decimal" :name="`variants[${index}][price]`" x-model="row.price" placeholder="19.99" required
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm tabular text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Compare At</label>
                                    <input type="text" inputmode="decimal" :name="`variants[${index}][compare_at_price]`" x-model="row.compare_at_price" placeholder="24.99"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm tabular text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-500">Cost</label>
                                    <input type="text" inputmode="decimal" :name="`variants[${index}][cost]`" x-model="row.cost" placeholder="8.00"
                                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm tabular text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                </div>
                                <label class="flex items-center gap-2.5 pb-2 cursor-pointer select-none">
                                    <input type="hidden" :name="`variants[${index}][track_inventory]`" :value="row.track_inventory ? 1 : 0">
                                    <button type="button" role="switch" :aria-checked="row.track_inventory.toString()" @click="row.track_inventory = ! row.track_inventory"
                                        :class="row.track_inventory ? 'bg-brand-600' : 'bg-slate-300'"
                                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors">
                                        <span :class="row.track_inventory ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
                                    </button>
                                    <span class="text-sm text-slate-700">Track Inventory</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <x-button type="button" variant="secondary" icon="plus" @click="addRow()">Add Variant</x-button>
                </div>
            </x-card>
        </div>

        {{-- Organization --}}
        <div x-show="tab === 'organization'" x-cloak class="space-y-6">
            <x-card title="Shipping">
                <x-toggle name="requires_shipping" :checked="old('requires_shipping', $product->requires_shipping)"
                    label="This Product Requires Shipping" description="Turn off for digital goods, services, or gift cards." />
            </x-card>
            <x-card title="Collections" subtitle="Group this product into storefront collections.">
                @if ($collections->isEmpty())
                    <x-empty-state icon="folder" title="No Collections Yet" description="Create a collection to organize products." />
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                        @foreach ($collections as $collection)
                            <div class="rounded-lg ring-1 ring-slate-200 px-3 py-2.5 hover:bg-slate-50 transition">
                                <x-check-switch name="collections[]" :value="$collection->id"
                                    :checked="in_array($collection->id, old('collections', $selectedCollections))">
                                    <span class="truncate">{{ $collection->name }}</span>
                                </x-check-switch>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        {{-- SEO --}}
        <div x-show="tab === 'seo'" x-cloak>
            <x-card title="Search Engine Listing" subtitle="Overrides the defaults used for search results and link previews.">
                <div class="space-y-5">
                    <x-field label="SEO Title" for="seo_title" hint="Blank falls back to the product name." :error="$errors->first('seo_title')">
                        <x-input id="seo_title" name="seo_title" :value="old('seo_title', $product->seo_title)" maxlength="255" />
                    </x-field>
                    <x-field label="SEO Description" for="seo_description" hint="Blank falls back to the excerpt." :error="$errors->first('seo_description')">
                        <textarea id="seo_description" name="seo_description" rows="3" maxlength="500"
                            class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('seo_description', $product->seo_description) }}</textarea>
                    </x-field>
                </div>
            </x-card>
        </div>

        <div class="flex items-center justify-end gap-2 pt-6 mt-6 border-t border-slate-100">
            <x-button variant="secondary" href="{{ route('products.index') }}">Cancel</x-button>
            <x-button type="submit" icon="check">{{ $isEdit ? 'Save Changes' : 'Create Product' }}</x-button>
        </div>
    </form>

    {{-- Inventory: a separate quick-adjust form, outside the main product form
         above (browsers cannot nest <form> elements), posting straight to
         products.inventory.update. Only meaningful once variants have ids. --}}
    <div x-show="tab === 'inventory'" x-cloak>
        @if ($isEdit && $product->variants->isNotEmpty())
            <x-card title="Inventory" subtitle="Adjust stock on hand. Saved immediately, independent of the form above.">
                <form method="POST" action="{{ route('products.inventory.update', $product) }}">
                    @csrf
                    @method('PUT')
                    <div class="divide-y divide-slate-100">
                        @foreach ($product->variants as $variant)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ $variant->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $variant->sku ?: 'No SKU' }} &middot; {{ $variant->track_inventory ? 'Tracked' : 'Not Tracked' }}</p>
                                </div>
                                <input type="number" name="quantities[{{ $variant->id }}]" value="{{ old('quantities.'.$variant->id, $variant->inventory_qty) }}"
                                    class="w-28 shrink-0 rounded-lg border-0 bg-white px-3 py-2 text-sm text-right tabular text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-end pt-4 mt-1 border-t border-slate-100">
                        <x-button type="submit" icon="check">Save Inventory</x-button>
                    </div>
                </form>
            </x-card>
        @else
            <x-card>
                <x-empty-state icon="box" title="Save The Product First" description="Add and save at least one variant in the Variants & Pricing tab, then come back here to adjust stock." />
            </x-card>
        @endif
    </div>

    {{-- Images: also a separate form for the same reason. --}}
    <div x-show="tab === 'images'" x-cloak>
        @if ($isEdit)
            <x-card title="Upload An Image" class="mb-6">
                <form method="POST" action="{{ route('products.images.store', $product) }}" enctype="multipart/form-data" x-data="imagePreview()" class="flex flex-wrap items-end gap-4">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">Image</label>
                        <input type="file" name="image" accept="image/*" required @change="onChange"
                            class="block text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                    </div>
                    <div class="space-y-1.5 flex-1 min-w-[12rem]">
                        <label class="block text-sm font-medium text-slate-700" for="alt">Alt Text</label>
                        <x-input id="alt" name="alt" placeholder="Describes the image" />
                    </div>
                    <img x-show="preview" :src="preview" x-cloak class="w-16 h-16 rounded-lg object-cover ring-1 ring-slate-200">
                    <x-button type="submit" icon="plus">Upload</x-button>
                </form>
            </x-card>

            @if ($product->images->isEmpty())
                <x-card><x-empty-state icon="folder" title="No Images Yet" description="Uploaded images appear in the gallery below." /></x-card>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($product->images as $image)
                        <div class="relative rounded-xl ring-1 ring-slate-200 bg-white overflow-hidden shadow-sm group">
                            <img src="{{ $image->url }}" alt="{{ $image->alt_text }}" class="w-full h-36 object-cover">
                            <div class="absolute top-2 right-2">
                                <x-delete-button :action="route('products.images.destroy', [$product, $image])" name="del-image-{{ $image->id }}"
                                    title="Remove Image?" message="This removes the image from the product gallery." />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <x-card>
                <x-empty-state icon="folder" title="Save The Product First" description="Create the product, then upload images from this tab." />
            </x-card>
        @endif
    </div>
</div>
