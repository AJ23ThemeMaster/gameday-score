@extends('errors::minimal')

@section('title', __('Servicio no disponible'))
@section('code', '503')
@section('message', __('El sistema esta en mantenimiento o sobrecargado. Vuelve a intentarlo en unos minutos.'))