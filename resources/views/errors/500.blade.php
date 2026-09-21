@extends('errors::minimal')

@section('title', __('Error del servidor'))
@section('code', '500')
@section('message', __('Ocurrio un error inesperado en el servidor. Nuestro equipo ha sido notificado. Intenta de nuevo en unos minutos.'))