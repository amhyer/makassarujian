@extends('layouts.app')

@section('content')
    <div class="space-y-6 pb-8">
        <!-- Top Row: 4 Info Boxes -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- CPU Traffic -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-center">
                <div class="rounded-md bg-indigo-600 p-3 text-white mr-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">CPU Traffic</p>
                    <p class="text-2xl font-bold text-slate-900">10 <span class="text-base font-medium">%</span></p>
                </div>
            </div>
            <!-- Likes -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-center">
                <div class="rounded-md bg-rose-500 p-3 text-white mr-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.514" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Likes</p>
                    <p class="text-2xl font-bold text-slate-900">41,410</p>
                </div>
            </div>
            <!-- Sales -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-center">
                <div class="rounded-md bg-emerald-500 p-3 text-white mr-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Sales</p>
                    <p class="text-2xl font-bold text-slate-900">760</p>
                </div>
            </div>
            <!-- New Members -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-center">
                <div class="rounded-md bg-amber-500 p-3 text-white mr-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">New Members</p>
                    <p class="text-2xl font-bold text-slate-900">2,000</p>
                </div>
            </div>
        </div>

        <!-- Monthly Recap Report -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h5 class="text-lg font-semibold text-slate-800">Monthly Recap Report</h5>
                <!-- Tools -->
                <div class="flex items-center space-x-2">
                    <button class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                        </svg></button>
                    <button class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2">
                        <p class="text-center font-semibold text-slate-700 mb-4">Sales: 1 Jan, 2023 - 30 Jul, 2023</p>
                        <div id="sales-chart"></div>
                    </div>
                    <div>
                        <p class="text-center font-semibold text-slate-700 mb-4">Goal Completion</p>

                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-600">Add Products to Cart</span>
                                <span class="font-semibold text-slate-800">160/200</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5">
                                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: 80%"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-600">Complete Purchase</span>
                                <span class="font-semibold text-slate-800">310/400</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5">
                                <div class="bg-rose-500 h-1.5 rounded-full" style="width: 75%"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-600">Visit Premium Page</span>
                                <span class="font-semibold text-slate-800">480/800</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5">
                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: 60%"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-600">Send Inquiries</span>
                                <span class="font-semibold text-slate-800">250/500</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5">
                                <div class="bg-amber-500 h-1.5 rounded-full" style="width: 50%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 rounded-b-lg">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center divide-x divide-slate-200">
                    <div>
                        <span class="text-emerald-500 text-sm font-semibold flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                    clip-rule="evenodd"></path>
                            </svg> 17%
                        </span>
                        <h5 class="text-lg font-bold text-slate-800 my-1">$35,210.43</h5>
                        <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Revenue</span>
                    </div>
                    <div>
                        <span class="text-sky-500 text-sm font-semibold flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg> 0%
                        </span>
                        <h5 class="text-lg font-bold text-slate-800 my-1">$10,390.90</h5>
                        <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Cost</span>
                    </div>
                    <div>
                        <span class="text-emerald-500 text-sm font-semibold flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                    clip-rule="evenodd"></path>
                            </svg> 20%
                        </span>
                        <h5 class="text-lg font-bold text-slate-800 my-1">$24,813.53</h5>
                        <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Profit</span>
                    </div>
                    <div>
                        <span class="text-rose-500 text-sm font-semibold flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg> 18%
                        </span>
                        <h5 class="text-lg font-bold text-slate-800 my-1">1200</h5>
                        <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Goal Completions</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Multi-column grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column (col-md-8) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Two sub-columns -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Direct Chat -->
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 flex flex-col h-[400px]">
                        <div
                            class="px-4 py-3 border-b border-slate-200 flex justify-between items-center bg-amber-500 text-white rounded-t-lg">
                            <h3 class="font-semibold">Direct Chat</h3>
                            <div class="flex items-center space-x-2">
                                <span class="bg-amber-600 px-2 py-0.5 rounded text-xs">3</span>
                            </div>
                        </div>
                        <div class="p-4 flex-1 overflow-y-auto space-y-4 bg-slate-50">
                            <!-- Message 1 -->
                            <div>
                                <div class="flex justify-between text-xs text-slate-500 mb-1">
                                    <span class="font-semibold text-slate-700">Alexander Pierce</span>
                                    <span>23 Jan 2:00 pm</span>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-300 shrink-0"></div>
                                    <div
                                        class="bg-slate-200 text-slate-700 rounded-lg p-2 text-sm border border-slate-300 relative">
                                        Is this template really for free? That's unbelievable!
                                    </div>
                                </div>
                            </div>
                            <!-- Message 2 -->
                            <div>
                                <div class="flex justify-between text-xs text-slate-500 mb-1">
                                    <span>23 Jan 2:05 pm</span>
                                    <span class="font-semibold text-slate-700">Sarah Bullock</span>
                                </div>
                                <div class="flex gap-3 flex-row-reverse">
                                    <div class="w-8 h-8 rounded-full bg-amber-200 shrink-0"></div>
                                    <div
                                        class="bg-amber-500 text-white rounded-lg p-2 text-sm border border-amber-600 relative">
                                        You better believe it!
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 border-t border-slate-200 bg-white rounded-b-lg">
                            <div class="flex gap-2">
                                <input type="text" placeholder="Type Message ..."
                                    class="w-full text-sm rounded-md border-slate-300 focus:border-amber-500 focus:ring focus:ring-amber-200">
                                <button
                                    class="px-4 py-2 bg-amber-500 text-white rounded-md text-sm hover:bg-amber-600">Send</button>
                            </div>
                        </div>
                    </div>

                    <!-- Latest Members -->
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 flex flex-col h-[400px]">
                        <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                            <h3 class="font-semibold text-slate-800">Latest Members</h3>
                            <span class="bg-rose-500 text-white px-2 py-0.5 rounded text-xs">8 New Members</span>
                        </div>
                        <div class="p-4 flex-1 overflow-y-auto">
                            <div class="grid grid-cols-4 gap-4 text-center">
                                @for ($i = 0; $i < 8; $i++)
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-full bg-indigo-100 mb-2"></div>
                                        <a href="#" class="text-xs font-semibold text-slate-700 truncate w-full">User
                                            {{ $i }}</a>
                                        <span class="text-[10px] text-slate-500">Today</span>
                                    </div>
                                @endfor
                            </div>
                        </div>
                        <div class="p-3 border-t border-slate-200 text-center rounded-b-lg">
                            <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold">View All
                                Users</a>
                        </div>
                    </div>
                </div>

                <!-- Latest Orders -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                    <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                        <h3 class="font-semibold text-slate-800">Latest Orders</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 font-semibold border-b border-slate-200">Order ID</th>
                                    <th class="px-4 py-3 font-semibold border-b border-slate-200">Item</th>
                                    <th class="px-4 py-3 font-semibold border-b border-slate-200">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr>
                                    <td class="px-4 py-3 text-indigo-600 hover:underline cursor-pointer">OR9842</td>
                                    <td class="px-4 py-3">Call of Duty IV</td>
                                    <td class="px-4 py-3"><span
                                            class="px-2 py-1 rounded text-xs bg-emerald-100 text-emerald-700">Shipped</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-indigo-600 hover:underline cursor-pointer">OR1848</td>
                                    <td class="px-4 py-3">Samsung Smart TV</td>
                                    <td class="px-4 py-3"><span
                                            class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-700">Pending</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-indigo-600 hover:underline cursor-pointer">OR7429</td>
                                    <td class="px-4 py-3">iPhone 6 Plus</td>
                                    <td class="px-4 py-3"><span
                                            class="px-2 py-1 rounded text-xs bg-rose-100 text-rose-700">Delivered</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-indigo-600 hover:underline cursor-pointer">OR7429</td>
                                    <td class="px-4 py-3">Samsung Smart TV</td>
                                    <td class="px-4 py-3"><span
                                            class="px-2 py-1 rounded text-xs bg-sky-100 text-sky-700">Processing</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-t border-slate-200 flex justify-between">
                        <a href="#"
                            class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 transition-colors">Place
                            New Order</a>
                        <a href="#"
                            class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded text-sm hover:bg-slate-200 transition-colors">View
                            All Orders</a>
                    </div>
                </div>

            </div>

            <!-- Right Column (col-md-4) -->
            <div class="space-y-6">
                <!-- 4 Mini Info Boxes -->
                <div class="bg-amber-500 rounded-lg shadow-sm p-4 text-white flex items-center">
                    <div class="mr-4 opacity-75">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Inventory</p>
                        <p class="text-xl font-bold">5,200</p>
                    </div>
                </div>

                <div class="bg-emerald-500 rounded-lg shadow-sm p-4 text-white flex items-center">
                    <div class="mr-4 opacity-75">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Mentions</p>
                        <p class="text-xl font-bold">92,050</p>
                    </div>
                </div>

                <div class="bg-rose-500 rounded-lg shadow-sm p-4 text-white flex items-center">
                    <div class="mr-4 opacity-75">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Downloads</p>
                        <p class="text-xl font-bold">114,381</p>
                    </div>
                </div>

                <div class="bg-sky-500 rounded-lg shadow-sm p-4 text-white flex items-center">
                    <div class="mr-4 opacity-75">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Direct Messages</p>
                        <p class="text-xl font-bold">163,921</p>
                    </div>
                </div>

                <!-- Browser Usage Pie Chart -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                    <div class="px-4 py-3 border-b border-slate-200">
                        <h3 class="font-semibold text-slate-800">Browser Usage</h3>
                    </div>
                    <div class="p-4">
                        <div id="pie-chart" class="flex justify-center"></div>
                    </div>
                    <div class="border-t border-slate-200">
                        <ul class="divide-y divide-slate-100 text-sm">
                            <li class="px-4 py-3 flex justify-between text-slate-600">
                                <span>United States of America</span>
                                <span class="text-rose-500 font-semibold">&darr; 12%</span>
                            </li>
                            <li class="px-4 py-3 flex justify-between text-slate-600">
                                <span>India</span>
                                <span class="text-emerald-500 font-semibold">&uarr; 4%</span>
                            </li>
                            <li class="px-4 py-3 flex justify-between text-slate-600">
                                <span>China</span>
                                <span class="text-sky-500 font-semibold">&larr; 0%</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Recently Added Products -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                    <div class="px-4 py-3 border-b border-slate-200">
                        <h3 class="font-semibold text-slate-800">Recently Added Products</h3>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        <li class="p-4 flex gap-4 items-center">
                            <div class="w-12 h-12 bg-slate-200 rounded shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-1">
                                    <a href="#" class="font-semibold text-slate-800 hover:text-indigo-600 truncate">Samsung
                                        TV</a>
                                    <span
                                        class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded shrink-0">$1800</span>
                                </div>
                                <p class="text-xs text-slate-500 truncate">Samsung 32" 1080p 60Hz LED Smart HDTV.</p>
                            </div>
                        </li>
                        <li class="p-4 flex gap-4 items-center">
                            <div class="w-12 h-12 bg-slate-200 rounded shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-1">
                                    <a href="#"
                                        class="font-semibold text-slate-800 hover:text-indigo-600 truncate">Bicycle</a>
                                    <span class="bg-sky-100 text-sky-800 text-xs px-2 py-0.5 rounded shrink-0">$700</span>
                                </div>
                                <p class="text-xs text-slate-500 truncate">26" Mongoose Dolomite Men's 7-speed, Navy Blue.
                                </p>
                            </div>
                        </li>
                        <li class="p-4 flex gap-4 items-center">
                            <div class="w-12 h-12 bg-slate-200 rounded shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-1">
                                    <a href="#" class="font-semibold text-slate-800 hover:text-indigo-600 truncate">Xbox
                                        One</a>
                                    <span class="bg-rose-100 text-rose-800 text-xs px-2 py-0.5 rounded shrink-0">$350</span>
                                </div>
                                <p class="text-xs text-slate-500 truncate">Xbox One Console Bundle with Halo Master Chief
                                    Collection.</p>
                            </div>
                        </li>
                    </ul>
                    <div class="p-3 border-t border-slate-200 text-center">
                        <a href="#" class="text-indigo-600 text-sm font-semibold hover:underline uppercase">View All
                            Products</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const sales_chart_options = {
                series: [
                    { name: 'Digital Goods', data: [28, 48, 40, 19, 86, 27, 90] },
                    { name: 'Electronics', data: [65, 59, 80, 81, 56, 55, 40] },
                ],
                chart: { height: 250, type: 'area', toolbar: { show: false } },
                legend: { show: false },
                colors: ['#4f46e5', '#10b981'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth' },
                xaxis: {
                    type: 'datetime',
                    categories: ['2023-01-01', '2023-02-01', '2023-03-01', '2023-04-01', '2023-05-01', '2023-06-01', '2023-07-01'],
                },
                tooltip: { x: { format: 'MMMM yyyy' } },
            };
            const sales_chart = new ApexCharts(document.querySelector('#sales-chart'), sales_chart_options);
            sales_chart.render();

            const pie_chart_options = {
                series: [700, 500, 400, 600, 300, 100],
                chart: { type: 'donut', height: 250 },
                labels: ['Chrome', 'Edge', 'FireFox', 'Safari', 'Opera', 'IE'],
                dataLabels: { enabled: false },
                colors: ['#4f46e5', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#94a3b8'],
            };
            const pie_chart = new ApexCharts(document.querySelector('#pie-chart'), pie_chart_options);
            pie_chart.render();
        });
    </script>
@endsection