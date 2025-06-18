<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->text('description');
            
            // Create morph columns manually without automatic indexes
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            
            $table->json('properties')->nullable();
            $table->boolean('discord_sent')->default(false);
            $table->timestamp('discord_sent_at')->nullable();
            $table->timestamps();

            // Create custom indexes
            $table->index(['event_type', 'created_at'], 'activity_logs_event_created_index');
            $table->index(['subject_type', 'subject_id'], 'activity_logs_subject_index');
            $table->index(['causer_type', 'causer_id'], 'activity_logs_causer_index');
            $table->index('discord_sent', 'activity_logs_discord_sent_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
