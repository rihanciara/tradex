<div style="border: 2px solid black;"> 

<!-- business information here -->

<div class="row" style="color: #000000 !important;">
		<!-- Logo -->
	<tbody style="border: 2px solid black;"> 
		<tr>
			<td class="text-center" style="line-height: 15px !important; padding-bottom: 10px !important">
			@if(empty($receipt_details->letter_head))
				@if(!empty($receipt_details->header_text))
					{!! $receipt_details->header_text !!}
				@endif

				@php
					$sub_headings = implode('<br/>', array_filter([$receipt_details->sub_heading_line1, $receipt_details->sub_heading_line2, $receipt_details->sub_heading_line3, $receipt_details->sub_heading_line4, $receipt_details->sub_heading_line5]));
				@endphp

				@if(!empty($sub_headings))
					<span>{!! $sub_headings !!}</span>
				@endif
			@endif
				@if(!empty($receipt_details->invoice_heading))
					<h2 style="font-weight: bold; font-size: 15px !important; margin-top: 10px;">{!! $receipt_details->invoice_heading !!}</h2>
				@endif
			</td>
		</tr>
			@if(!empty($receipt_details->letter_head))
				<tr>
					<td>
						<img style="width: 100%;margin-bottom: 10px;" src="{{$receipt_details->letter_head}}">
					</td>
				</tr>
			@endif
		    <tr>
			<td>

<!-- business information here -->
<div class="row invoice-info">

	<div class="col-md-6 invoice-col width-50">

		<div class="text-right">
    @if(!empty($receipt_details->invoice_no_prefix))
        <span class="pull-left" style="margin-left: 18px;">{!! $receipt_details->invoice_no_prefix !!}</span>
    @endif

    {{$receipt_details->invoice_no}}
</div>

<!-- Date -->
@if(!empty($receipt_details->date_label))
    <div class="text-right" >
        <span class="pull-left" style="margin-left: 18px;">
            {{$receipt_details->date_label}}
        </span>

        {{$receipt_details->invoice_date}}
    </div>
@endif

@if(!empty($receipt_details->due_date_label))
    <div class="text-right">
        <span class="pull-left">
            {{$receipt_details->due_date_label}}
        </span>

        {{$receipt_details->due_date ?? ''}}
    </div>
@endif

@if(!empty($receipt_details->sell_custom_field_1_value))
    <div class="text-right">
        <span class="pull-left">
            {{$receipt_details->sell_custom_field_1_label}}
        </span>

        {{$receipt_details->sell_custom_field_1_value}}
           <br> <!-- New line after custom field 1 -->
    </div>
   
@endif
@if(!empty($receipt_details->sell_custom_field_2_value))
    <div class="text-right">
        <span class="pull-left"  style="margin-left: 18px;">
            {{$receipt_details->sell_custom_field_2_label}}
        </span>

        {{$receipt_details->sell_custom_field_2_value}}
    </div>
      <br> <!-- New line after custom field 1 -->
@endif
@if(!empty($receipt_details->sell_custom_field_3_value))
    <div class="text-right">
        <span class="pull-left">
            {{$receipt_details->sell_custom_field_3_label}}
        </span>

        {{$receipt_details->sell_custom_field_3_value}}
    </div>
      <br> <!-- New line after custom field 1 -->
@endif
@if(!empty($receipt_details->sell_custom_field_4_value))
    <div class="text-right">
        <span class="pull-left">
            {{$receipt_details->sell_custom_field_4_label}}
        </span>

        {{$receipt_details->sell_custom_field_4_value}}
    </div>
@endif


		<div class="word-wrap" style="text-align: left;  margin-left: 18px;" >
			@if(!empty($receipt_details->customer_label))
				<b>{{ $receipt_details->customer_label }}</b><br/>
			@endif

			<!-- customer info -->
			@if(!empty($receipt_details->customer_info))
				{!! $receipt_details->customer_info !!}
			@endif
			@if(!empty($receipt_details->client_id_label))
				<br/>
				<strong>{{ $receipt_details->client_id_label }}</strong> {{ $receipt_details->client_id }}
			@endif
			@if(!empty($receipt_details->customer_tax_label))
				<br/>
				<strong>{{ $receipt_details->customer_tax_label }}</strong> {{ $receipt_details->customer_tax_number }}
			@endif
			@if(!empty($receipt_details->customer_custom_fields))
				<br/>{!! $receipt_details->customer_custom_fields !!}
			@endif
			@if(!empty($receipt_details->sales_person_label))
				<br/>
				<strong>{{ $receipt_details->sales_person_label }}</strong> {{ $receipt_details->sales_person }}
			@endif

			@if(!empty($receipt_details->commission_agent_label))
				<br/>
				<strong>{{ $receipt_details->commission_agent_label }}</strong> {{ $receipt_details->commission_agent }}
			@endif

			@if(!empty($receipt_details->customer_rp_label))
				<br/>
				<strong>{{ $receipt_details->customer_rp_label }}</strong> {{ $receipt_details->customer_total_rp }}
			@endif

			<!-- Display type of service details -->
			@if(!empty($receipt_details->types_of_service))
				<span class="pull-left text-left">
					<strong>{!! $receipt_details->types_of_service_label !!}:</strong>
					{{$receipt_details->types_of_service}}
					<!-- Waiter info -->
					@if(!empty($receipt_details->types_of_service_custom_fields))
						<br>
						@foreach($receipt_details->types_of_service_custom_fields as $key => $value)
							<strong>{{$key}}: </strong> {{$value}}@if(!$loop->last), @endif
						@endforeach
					@endif
				</span>
			@endif
		</div>

	</div>
                  

	<div class="col-md-6 invoice-col width-50" >
		@if(empty($receipt_details->letter_head))
		<!-- Logo -->
		@if(!empty($receipt_details->logo))
   			 <img style="max-height: 130px; width: 93%; padding-top: 15px;" src="{{$receipt_details->logo}}" >
    	<br>
		@endif


		<!-- Shop & Location Name  -->
		
			<span>
				@if(!empty($receipt_details->display_name))
				<h2 style="font-weight: bold; font-size: 18px !important; margin-top: 5px;">{{$receipt_details->display_name}}</h2>
	
					<br/>
				@endif

				@if(!empty($receipt_details->address))
					{!! $receipt_details->address !!}
				@endif

				@if(!empty($receipt_details->contact))
					<br/>{!! $receipt_details->contact !!}
				@endif

				@if(!empty($receipt_details->website))
					<br/>{{ $receipt_details->website }}
				@endif

				@if(!empty($receipt_details->tax_info1))
					<br/>{{ $receipt_details->tax_label1 }} {{ $receipt_details->tax_info1 }}
				@endif

				@if(!empty($receipt_details->tax_info2))
					<br/>{{ $receipt_details->tax_label2 }} {{ $receipt_details->tax_info2 }}
				@endif

				@if(!empty($receipt_details->location_custom_fields))
					<br/>{{ $receipt_details->location_custom_fields }}
				@endif
			</span>
		@endif
		<!-- Table information-->
        @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
        	<p>
				@if(!empty($receipt_details->table_label))
					{!! $receipt_details->table_label !!}
				@endif
				{{$receipt_details->table}}
			</p>
        @endif

		<!-- Waiter info -->
		@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
        	<p>
				@if(!empty($receipt_details->service_staff_label))
					{!! $receipt_details->service_staff_label !!}
				@endif
				{{$receipt_details->service_staff}}
			</p>
        @endif



        <div class="word-wrap">

			<p class="text-right ">

			@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
				@if(!empty($receipt_details->brand_label))
					<span class="pull-left">
						<strong>{!! $receipt_details->brand_label !!}</strong>
					</span>
				@endif
				{{$receipt_details->repair_brand}}<br>
	        @endif


	        @if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
				@if(!empty($receipt_details->device_label))
					<span class="pull-left">
						<strong>{!! $receipt_details->device_label !!}</strong>
					</span>
				@endif
				{{$receipt_details->repair_device}}<br>
	        @endif
	        
			@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
				@if(!empty($receipt_details->model_no_label))
					<span class="pull-left">
						<strong>{!! $receipt_details->model_no_label !!}</strong>
					</span>
				@endif
				{{$receipt_details->repair_model_no}} <br>
	        @endif

			@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
				@if(!empty($receipt_details->serial_no_label))
					<span class="pull-left">
						<strong>{!! $receipt_details->serial_no_label !!}</strong>
					</span>
				@endif
				{{$receipt_details->repair_serial_no}}<br>
	        @endif
			@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
				@if(!empty($receipt_details->repair_status_label))
					<span class="pull-left">
						<strong>{!! $receipt_details->repair_status_label !!}</strong>
					</span>
				@endif
				{{$receipt_details->repair_status}}<br>
	        @endif
	        
	        @if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
				@if(!empty($receipt_details->repair_warranty_label))
					<span class="pull-left">
						<strong>{!! $receipt_details->repair_warranty_label !!}</strong>
					</span>
				@endif
				{{$receipt_details->repair_warranty}}
				<br>
	        @endif
	        </p>
		</div>
	</div>
</div>
@if(!empty($receipt_details->shipping_custom_field_1_label) || !empty($receipt_details->shipping_custom_field_2_label))
	<div class="row">
		<div class="col-xs-6">
			@if(!empty($receipt_details->shipping_custom_field_1_label))
				<strong>{!!$receipt_details->shipping_custom_field_1_label!!} :</strong> {!!$receipt_details->shipping_custom_field_1_value ?? ''!!}
			@endif
		</div>
		<div class="col-xs-6">
			@if(!empty($receipt_details->shipping_custom_field_2_label))
				<strong>{!!$receipt_details->shipping_custom_field_2_label!!}:</strong> {!!$receipt_details->shipping_custom_field_2_value ?? ''!!}
			@endif
		</div>
	</div>
@endif
@if(!empty($receipt_details->shipping_custom_field_3_label) || !empty($receipt_details->shipping_custom_field_4_label))
	<div class="row">
		<div class="col-xs-6">
			@if(!empty($receipt_details->shipping_custom_field_3_label))
				<strong>{!!$receipt_details->shipping_custom_field_3_label!!} :</strong> {!!$receipt_details->shipping_custom_field_3_value ?? ''!!}
			@endif
		</div>
		<div class="col-xs-6">
			@if(!empty($receipt_details->shipping_custom_field_4_label))
				<strong>{!!$receipt_details->shipping_custom_field_4_label!!}:</strong> {!!$receipt_details->shipping_custom_field_4_value ?? ''!!}
			@endif
		</div>
	</div>
@endif
@if(!empty($receipt_details->shipping_custom_field_5_label))
	<div class="row">
		<div class="col-xs-6">
			@if(!empty($receipt_details->shipping_custom_field_5_label))
				<strong>{!!$receipt_details->shipping_custom_field_5_label!!} :</strong> {!!$receipt_details->shipping_custom_field_5_value ?? ''!!}
			@endif
		</div>
	</div>
@endif
@if(!empty($receipt_details->sale_orders_invoice_no) || !empty($receipt_details->sale_orders_invoice_date))
	<div class="row">
		<div class="col-xs-6">
			<strong>@lang('restaurant.order_no'):</strong> {!!$receipt_details->sale_orders_invoice_no ?? ''!!}
		</div>
		<div class="col-xs-6">
			<strong>@lang('lang_v1.order_dates'):</strong> {!!$receipt_details->sale_orders_invoice_date ?? ''!!}
		</div>
	</div>
@endif
<div class="row">
	@includeIf('sale_pos.receipts.partial.common_repair_invoice')
</div>
          </div>

<div class="row" style="color: #000000 !important; padding: 10px;" >
    <div class="col-xs-12">
        <br/>
        @php
            $p_width = 45;
        @endphp
        @if($receipt_details->show_cat_code == 1)
                        @php
                            $p_width -= 10;
                        @endphp
        @endif
        @if(!empty($receipt_details->item_discount_label))
            @php
                $p_width -= 10;
            @endphp
        @endif
        @if(!empty($receipt_details->discounted_unit_price_label))
            @php
                $p_width -= 10;
            @endphp
        @endif
        <table class="table table-responsive table-slim" style="border-collapse: collapse;" >
            <thead>
                <tr style="border: 1px solid #000;">
                    <td style="width: 5%; border: 1px solid black;"> &nbsp;#</td>
                    <th width="{{$p_width}}%" style="border: 1px solid #000;">{{$receipt_details->table_product_label}}</th>
           			 @if($receipt_details->show_cat_code == 1)
                        <td style="width: 10%; border: 1px solid black; text-align: center;">
                            {{$receipt_details->cat_code_label}}
                        </td>
                    @endif
                    <th class="text-right" width="15%" style="border: 1px solid #000;">{{$receipt_details->table_qty_label}}</th>
                    <th class="text-right" width="15%" style="border: 1px solid #000;">{{$receipt_details->table_unit_price_label}}</th>
                    @if(!empty($receipt_details->discounted_unit_price_label))
                        <th class="text-right" width="10%" style="border: 1px solid #000;">{{$receipt_details->discounted_unit_price_label}}</th>
                    @endif
                    @if(!empty($receipt_details->item_discount_label))
                        <th class="text-right" width="10%" style="border: 1px solid #000;">{{$receipt_details->item_discount_label}}</th>
                    @endif
                    <th class="text-right" width="15%" style="border: 1px solid #000;">{{$receipt_details->table_subtotal_label}}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipt_details->lines as $line)
                    <tr style="border: 1px solid black;">
                        <td class="text-center" style="border: 1px solid black;">
                            {{$loop->iteration}}
                        </td>
                        <td style="border: 1px solid #000;">
                            @if(!empty($line['image']))
                                <img src="{{$line['image']}}" alt="Image" width="50" style="float: left; margin-right: 8px;">
                            @endif
                          &nbsp;  {{$line['name']}} {{$line['product_variation']}} {{$line['variation']}} 
                            @if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif 
                            @if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
                            @if(!empty($line['product_description']))
                                <small>
                                    {!!$line['product_description']!!}
                                </small>
                            @endif 
                            @if(!empty($line['sell_line_note']))
                            <br>
                            <small>
                                {!!$line['sell_line_note']!!}
                            </small>
                            @endif 
                            @if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}:  {{$line['lot_number']}} @endif 
                            @if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:  {{$line['product_expiry']}} @endif

                            @if(!empty($line['warranty_name'])) <br><small>{{$line['warranty_name']}} </small>@endif @if(!empty($line['warranty_exp_date'])) <small>- {{@format_date($line['warranty_exp_date'])}} </small>@endif
                            @if(!empty($line['warranty_description'])) <small> {{$line['warranty_description'] ?? ''}}</small>@endif

                            @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                            <br><small>
                                1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}} <br>
                                {{$line['base_unit_price']}} x {{$line['orig_quantity']}} = {{$line['line_total']}}
                            </small>
                            @endif
                        </td>
                            
                            
						@if($receipt_details->show_cat_code == 1)
	                        <td style="border: 1px solid black;">
	                        	@if(!empty($line['cat_code']))
	                        		{{$line['cat_code']}}
	                        	@endif
	                        </td>
	                    @endif
                        <td class="text-right" style="border: 1px solid #000;">
                            {{$line['quantity']}} {{$line['units']}} 

                            @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                            <br><small>
                                {{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}
                            </small>
                            @endif
                        </td>
                        <td class="text-right" style="border: 1px solid #000;">{{$line['unit_price_before_discount']}}</td>
                        @if(!empty($receipt_details->discounted_unit_price_label))
                            <td class="text-right" style="border: 1px solid #000;">
                                {{$line['unit_price_inc_tax']}} 
                            </td>
                        @endif
                        @if(!empty($receipt_details->item_discount_label))
                            <td class="text-right" style="border: 1px solid #000;">
                                {{$line['total_line_discount'] ?? '0.00'}}

                                @if(!empty($line['line_discount_percent']))
                                 	({{$line['line_discount_percent']}}%)
                                @endif
                            </td>
                        @endif
                        <td class="text-right" style="border: 1px solid #000;">{{$line['line_total']}}</td>
                    </tr>
                    @if(!empty($line['modifiers']))
                        @foreach($line['modifiers'] as $modifier)
                            <tr style="border: 1px solid #000;">
                                <td style="border: 1px solid #000;">
                                    {{$modifier['name']}} {{$modifier['variation']}} 
                                    @if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif @if(!empty($modifier['cat_code'])), {{$modifier['cat_code']}}@endif
                                    @if(!empty($modifier['sell_line_note']))({!!$modifier['sell_line_note']!!}) @endif 
                                </td>
                                <td class="text-right" style="border: 1px solid #000;">{{$modifier['quantity']}} {{$modifier['units']}} </td>
                                <td class="text-right" style="border: 1px solid #000;">{{$modifier['unit_price_inc_tax']}}</td>
                                @if(!empty($receipt_details->discounted_unit_price_label))
                                    <td class="text-right" style="border: 1px solid #000;">{{$modifier['unit_price_exc_tax']}}</td>
                                @endif
                                @if(!empty($receipt_details->item_discount_label))
                                    <td class="text-right" style="border: 1px solid #000;">0.00</td>
                                @endif
                                <td class="text-right" style="border: 1px solid #000;">{{$modifier['line_total']}}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="4">&nbsp;</td>
                        @if(!empty($receipt_details->discounted_unit_price_label))
                            <td></td>
                        @endif
                        @if(!empty($receipt_details->item_discount_label))
                            <td></td>
                        @endif
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
                      

<!-- edited by thufail -->
<div class="row" style="color: #000000 !important;">
	<div class="col-md-12"><hr/></div>
	<div class="col-xs-6">

		<table class="table table-slim">

			@if(!empty($receipt_details->payments))
				@foreach($receipt_details->payments as $payment)
					<tr>
						<td>{{$payment['method']}}</td>
						<td class="text-right" >{{$payment['amount']}}</td>
						<td class="text-right">{{$payment['date']}}</td>
					</tr>
				@endforeach
			@endif

			<!-- Total Paid-->
			@if(!empty($receipt_details->total_paid))
				<tr>
					<th>
						{!! $receipt_details->total_paid_label !!}
					</th>
					<td class="text-right">
						{{$receipt_details->total_paid}}
					</td>
				</tr>
			@endif

			<!-- Total Due-->
			@if(!empty($receipt_details->total_due) && !empty($receipt_details->total_due_label))
			<tr>
				<th>
					{!! $receipt_details->total_due_label !!}
				</th>
				<td class="text-right">
					{{$receipt_details->total_due}}
				</td>
			</tr>
			@endif

			@if(!empty($receipt_details->all_due))
			<tr>
				<th>
					{!! $receipt_details->all_bal_label !!}
				</th>
				<td class="text-right">
					{{$receipt_details->all_due}}
				</td>
			</tr>
			@endif
		</table>
		         <br><br><br>
		<b class="pull-left">{{__('lang_v1.authorized_signatory')}}</b>
	</div>

	<div class="col-xs-6">
        <div class="table-responsive">
          	<table class="table table-slim">
				<tbody>
					@if(!empty($receipt_details->total_quantity_label))
						<tr>
							<th style="width:70%">
								{!! $receipt_details->total_quantity_label !!}
							</th>
							<td class="text-right">
								{{$receipt_details->total_quantity}}
							</td>
						</tr>
					@endif

					@if(!empty($receipt_details->total_items_label))
						<tr>
							<th style="width:70%">
								{!! $receipt_details->total_items_label !!}
							</th>
							<td class="text-right">
								{{$receipt_details->total_items}}
							</td>
						</tr>
					@endif
					<tr>
						<th style="width:70%">
							{!! $receipt_details->subtotal_label !!}
						</th>
						<td class="text-right">
							{{$receipt_details->subtotal}}
						</td>
					</tr>
					@if(!empty($receipt_details->total_exempt_uf))
					<tr>
						<th style="width:70%">
							@lang('lang_v1.exempt')
						</th>
						<td class="text-right">
							{{$receipt_details->total_exempt}}
						</td>
					</tr>
					@endif
					<!-- Shipping Charges -->
					@if(!empty($receipt_details->shipping_charges))
						<tr>
							<th style="width:70%">
								{!! $receipt_details->shipping_charges_label !!}
							</th>
							<td class="text-right">
								{{$receipt_details->shipping_charges}}
							</td>
						</tr>
					@endif

					@if(!empty($receipt_details->packing_charge))
						<tr>
							<th style="width:70%">
								{!! $receipt_details->packing_charge_label !!}
							</th>
							<td class="text-right">
								{{$receipt_details->packing_charge}}
							</td>
						</tr>
					@endif

					<!-- Discount -->
					@if( !empty($receipt_details->discount) )
						<tr>
							<th>
								{!! $receipt_details->discount_label !!}
							</th>

							<td class="text-right">
								(-) {{$receipt_details->discount}}
							</td>
						</tr>
					@endif

					@if( !empty($receipt_details->total_line_discount) )
						<tr>
							<th>
								{!! $receipt_details->line_discount_label !!}
							</th>

							<td class="text-right">
								(-) {{$receipt_details->total_line_discount}}
							</td>
						</tr>
					@endif

					@if( !empty($receipt_details->additional_expenses) )
						@foreach($receipt_details->additional_expenses as $key => $val)
							<tr>
								<td>
									{{$key}}:
								</td>

								<td class="text-right">
									(+) {{$val}}
								</td>
							</tr>
						@endforeach
					@endif

					@if( !empty($receipt_details->reward_point_label) )
						<tr>
							<th>
								{!! $receipt_details->reward_point_label !!}
							</th>

							<td class="text-right">
								(-) {{$receipt_details->reward_point_amount}}
							</td>
						</tr>
					@endif

					<!-- Tax -->
					@if( !empty($receipt_details->tax) )
						<tr>
							<th>
								{!! $receipt_details->tax_label !!}
							</th>
							<td class="text-right">
								(+) {{$receipt_details->tax}}
							</td>
						</tr>
					@endif

					@if( $receipt_details->round_off_amount > 0)
						<tr>
							<th>
								{!! $receipt_details->round_off_label !!}
							</th>
							<td class="text-right">
								{{$receipt_details->round_off}}
							</td>
						</tr>
					@endif

					<!-- Total -->
					<tr>
						<th>
							{!! $receipt_details->total_label !!}
						</th>
						<td class="text-right">
							{{$receipt_details->total}}
							@if(!empty($receipt_details->total_in_words))
								<br>
								<small>({{$receipt_details->total_in_words}})</small>
							@endif
						</td>
					</tr>
				</tbody>
        	</table>
        </div>
    </div>
    <!-- tmk code end here -->

    <div class="border-bottom col-md-12">
	    @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) )
	        <!-- tax -->
	        @if(!empty($receipt_details->taxes))
	        	<table class="table table-slim table-bordered">
	        		<tr>
	        			<th colspan="2" class="text-center">{{$receipt_details->tax_summary_label}}</th>
	        		</tr>
	        		@foreach($receipt_details->taxes as $key => $val)
	        			<tr>
	        				<td class="text-center"><b>{{$key}}</b></td>
	        				<td class="text-center">{{$val}}</td>
	        			</tr>
	        		@endforeach
	        	</table>
	        @endif
	    @endif
	</div>

	@if(!empty($receipt_details->additional_notes))
	    <div class="col-xs-12">
	    	<p>{!! nl2br($receipt_details->additional_notes) !!}</p>
	    </div>
    @endif
    
</div>
<div class="row" style="color: #000000 !important;">
	@if(!empty($receipt_details->footer_text))
	<div class="@if($receipt_details->show_barcode || $receipt_details->show_qr_code) col-xs-8 @else col-xs-12 @endif">
		{!! $receipt_details->footer_text !!}
	</div>
	@endif
	@if($receipt_details->show_barcode || $receipt_details->show_qr_code)
		<div class="@if(!empty($receipt_details->footer_text)) col-xs-4 @else col-xs-12 @endif text-center">
			@if($receipt_details->show_barcode)
				{{-- Barcode --}}
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			@endif
			
			@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
				<img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}">
			@endif
		</div>
	@endif
</div>
   
         </td>
		</tr>
	</tbody>
</table>       
                
   </div>           