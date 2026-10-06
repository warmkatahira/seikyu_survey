<?php

/*
 * Validation messages in Japanese, worded for the people filling in the forms. The answer form
 * swaps 「入力してください」 for 「選択してください」 on its choice questions (see
 * SurveyResponseRequest::messages()). Field names come from each request's attributes().
 */
return [
    'accepted' => ':attributeを承認してください。',
    'array' => ':attributeを選択してください。',
    'between' => [
        'numeric' => ':attributeは:min〜:maxの範囲で入力してください。',
        'string' => ':attributeは:min〜:max文字で入力してください。',
        'array' => ':attributeは:min〜:max個選択してください。',
        'file' => ':attributeは:min〜:maxKBのファイルにしてください。',
    ],
    'boolean' => ':attributeの値が正しくありません。',
    'confirmed' => ':attributeが確認用と一致しません。',
    'date' => ':attributeには正しい日付を入力してください。',
    'distinct' => ':attributeに同じ値が重複しています。',
    'email' => ':attributeには正しいメールアドレスを入力してください。',
    'exists' => '選択された:attributeは存在しません。もう一度選択してください。',
    'file' => ':attributeにはファイルを指定してください。',
    'filled' => ':attributeを入力してください。',
    'in' => '選択された:attributeは正しくありません。もう一度選択してください。',
    'integer' => ':attributeは整数で入力してください。',
    'max' => [
        'numeric' => ':attributeは:max以下で入力してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
        'array' => ':attributeは:max個以内で選択してください。',
        'file' => ':attributeは:maxKB以下のファイルにしてください。',
    ],
    'mimes' => ':attributeは:values形式のファイルにしてください。',
    'mimetypes' => ':attributeの形式が正しくありません。CSVファイルを指定してください。',
    'min' => [
        'numeric' => ':attributeは:min以上で入力してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
        'array' => ':attributeは:min個以上選択してください。',
        'file' => ':attributeは:minKB以上のファイルにしてください。',
    ],
    'numeric' => ':attributeは数値で入力してください。',
    'present' => ':attributeが送信されていません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeを入力してください。',
    'required_if' => ':attributeを入力してください。',
    'required_with' => ':attributeを入力してください。',
    'size' => [
        'numeric' => ':attributeは:sizeにしてください。',
        'string' => ':attributeは:size文字にしてください。',
        'array' => ':attributeは:size個選択してください。',
        'file' => ':attributeは:sizeKBのファイルにしてください。',
    ],
    'string' => ':attributeは文字で入力してください。',
    'unique' => 'この:attributeはすでに使われています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',

    'custom' => [],

    'attributes' => [
        'login_id' => 'ログインID',
        'password' => 'パスワード',
    ],
];
