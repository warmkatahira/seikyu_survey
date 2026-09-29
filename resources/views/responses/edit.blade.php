<x-layout title="回答の編集" heading="回答の編集"
    :subheading="$response->customer?->name">
    @include('responses.form', ['action' => route('responses.update', $response)])
</x-layout>
