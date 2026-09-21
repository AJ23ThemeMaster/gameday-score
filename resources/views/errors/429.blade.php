@extends('errors::minimal')

@section('title', __('Demasiadas solicitudes'))
@section('code', '429')
@section('message', __('Has realizado demasiadas solicitudes en poco tiempo. Espera un momento e intenta de nuevo.'))