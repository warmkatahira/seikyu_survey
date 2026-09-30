<x-layout title="新規回答" heading="新しい顧客の回答を登録"
    subheading="担当している顧客を1社（請求書1通＝請求書番号）につき1件ずつ登録してください。分からない項目は「（未回答）」のままで構いません。">
    @include('responses.form', ['action' => route('responses.store')])
</x-layout>
