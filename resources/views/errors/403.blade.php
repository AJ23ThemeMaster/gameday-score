@extends('errors::minimal')

@section('title', __('Acceso denegado'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'No tienes permisos para acceder a esta pagina.'))