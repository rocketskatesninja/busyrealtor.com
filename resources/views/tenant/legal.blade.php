@extends('layouts.tenant')
@section('hide_header')@endsection
@section('hide_chatbot')@endsection
@section('title', ($page->title ?? 'Legal') . ' — ' . ($settings->site_title ?? 'BusyRealtor'))
@section('content')
<div class="max-w-4xl mx-auto px-4 py-16">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ $page->title ?? ($page->page_type === 'privacy' ? 'Privacy Policy' : 'Terms of Service') }}</h1>
    <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed">
        {!! nl2br(e($page->content ?? 'Content not yet available.')) !!}
    </div>
</div>
@endsection
