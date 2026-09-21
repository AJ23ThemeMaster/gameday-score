@extends('errors::minimal')

@section('title', __('Sesion expirada'))
@section('code', '419')
@section('message', __('Tu sesion expiro por inactividad. Recarga la pagina e inicia sesion nuevamente.'))