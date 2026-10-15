<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiAnalysisLog extends Model { protected $fillable=['product_id','raw_ocr_text','detected_date','confidence_score','raw_ai_response']; protected function casts():array{return ['detected_date'=>'date','raw_ai_response'=>'array'];} public function product(){return $this->belongsTo(Product::class);} }
