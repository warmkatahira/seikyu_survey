<x-layout title="新規回答" heading="新しい顧客の回答"
    subheading="担当している顧客を1社（請求書1通＝請求書番号）につき1件ずつ登録してください。">
    @include('responses.form', ['action' => route('responses.store')])
</x-layout>
