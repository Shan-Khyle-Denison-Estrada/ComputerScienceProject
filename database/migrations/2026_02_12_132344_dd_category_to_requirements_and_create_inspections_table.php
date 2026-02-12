<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Add category to requirements (Evaluation vs Inspection)
        Schema::table('application_requirements', function (Blueprint $table) {
            // 'evaluation' = File Upload (Default), 'inspection' = Admin Checklist
            $table->string('category')->default('evaluation')->after('name'); 
        });

        // 2. Table to store Admin's Inspection Results
        Schema::create('application_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('remarks')->nullable();
            $table->integer('score')->nullable(); // Or decimal, depending on your needs
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('application_inspections');
        Schema::table('application_requirements', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};