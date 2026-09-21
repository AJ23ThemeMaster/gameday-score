@extends('errors::minimal')

@section('title', __('No autorizado'))
@section('code', '401')
@section('message', __('Necesitas iniciar sesion para acceder a esta pagina.'))