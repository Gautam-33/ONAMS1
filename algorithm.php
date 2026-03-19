<?php
/**
 * COSINE SIMILARITY ALGORITHM FOR DOCTOR RECOMMENDATION
 * ======================================================
 * This algorithm recommends doctors to patients based on cosine similarity
 * between patient problem description and doctor attributes.
 * 
 * Algorithm: Cosine Similarity
 * Time Complexity: O(n*m) where n = number of doctors, m = features
 * Space Complexity: O(n)
 */

require_once 'config/database.php';

class CosineSimilarityRecommendation {
    private $db;
    
    /**
     * Problem keywords to medical specialization mapping
     * Each keyword maps to a specific medical specialty
     */
    private $keywordToSpecialization = [
        // Cardiology
        'heart' => 'Cardiology',
        'chest pain' => 'Cardiology',
        'cardiac' => 'Cardiology',
        'heartbeat' => 'Cardiology',
        'palpitation' => 'Cardiology',
        'blood pressure' => 'Cardiology',
        'hypertension' => 'Cardiology',
        'arrhythmia' => 'Cardiology',
        
        // Dermatology
        'skin' => 'Dermatology',
        'rash' => 'Dermatology',
        'acne' => 'Dermatology',
        'eczema' => 'Dermatology',
        'psoriasis' => 'Dermatology',
        'dermatitis' => 'Dermatology',
        'skin disease' => 'Dermatology',
        
        // Neurology
        'brain' => 'Neurology',
        'headache' => 'Neurology',
        'migraine' => 'Neurology',
        'nerve' => 'Neurology',
        'seizure' => 'Neurology',
        'epilepsy' => 'Neurology',
        'stroke' => 'Neurology',
        'memory' => 'Neurology',
        
        // Orthopedics
        'bone' => 'Orthopedics',
        'joint' => 'Orthopedics',
        'fracture' => 'Orthopedics',
        'back pain' => 'Orthopedics',
        'neck pain' => 'Orthopedics',
        'arthritis' => 'Orthopedics',
        'spine' => 'Orthopedics',
        'sports injury' => 'Orthopedics',
        
        // Pediatrics
        'child' => 'Pediatrics',
        'baby' => 'Pediatrics',
        'kids' => 'Pediatrics',
        'infant' => 'Pediatrics',
        'newborn' => 'Pediatrics',
        'pediatric' => 'Pediatrics',
        
        // Psychiatry
        'mental' => 'Psychiatry',
        'depression' => 'Psychiatry',
        'anxiety' => 'Psychiatry',
        'stress' => 'Psychiatry',
        'psychiatric' => 'Psychiatry',
        'mental health' => 'Psychiatry',
        'panic' => 'Psychiatry',
        
        // Ophthalmology
        'eye' => 'Ophthalmology',
        'vision' => 'Ophthalmology',
        'eye pain' => 'Ophthalmology',
        'blind' => 'Ophthalmology',
        'cataract' => 'Ophthalmology',
        'glaucoma' => 'Ophthalmology',
        
        // ENT (Ear, Nose, Throat)
        'ear' => 'ENT',
        'nose' => 'ENT',
        'throat' => 'ENT',
        'sinus' => 'ENT',
        'hearing' => 'ENT',
        'tonsil' => 'ENT',
        'ear pain' => 'ENT',
        
        // Nephrology
        'kidney' => 'Nephrology',
        'urinary' => 'Nephrology',
        'kidney stone' => 'Nephrology',
        'urine' => 'Nephrology',
        'bladder' => 'Nephrology',
        
        // Endocrinology
        'diabetes' => 'Endocrinology',
        'thyroid' => 'Endocrinology',
        'hormone' => 'Endocrinology',
        'sugar' => 'Endocrinology',
        'metabolism' => 'Endocrinology',
        
        // General Practice
        'fever' => 'General Practice',
        'cough' => 'General Practice',
        'cold' => 'General Practice',
        'flu' => 'General Practice',
        'general' => 'General Practice',
        'common illness' => 'General Practice',
        
        // Gastroenterology
        'stomach' => 'Gastroenterology',
        'digestion' => 'Gastroenterology',
        'liver' => 'Gastroenterology',
        'intestine' => 'Gastroenterology',
        'gastric' => 'Gastroenterology',
        'constipation' => 'Gastroenterology',
        
        // Pulmonology
        'lung' => 'Pulmonology',
        'breathing' => 'Pulmonology',
        'asthma' => 'Pulmonology',
        'respiratory' => 'Pulmonology',
        'breath' => 'Pulmonology',
        'coughing' => 'Pulmonology',
        
        // Oncology
        'cancer' => 'Oncology',
        'tumor' => 'Oncology',
        'malignant' => 'Oncology',
        'chemotherapy' => 'Oncology',
        
        // Gynecology
        'pregnancy' => 'Gynecology',
        'menstrual' => 'Gynecology',
        'period' => 'Gynecology',
        'women' => 'Gynecology',
        'uterus' => 'Gynecology',
        'ovary' => 'Gynecology',
        
        // Urology
        'prostate' => 'Urology',
        'male' => 'Urology',
        'impotence' => 'Urology',
    ];
    
    /**
     * Feature weights for cosine similarity calculation
     * Higher weight = more important in recommendation
     */
    private $featureWeights = [
        'specialisation' => 5.0,  // Most important - specialty match
        'experience' => 2.0,     // Years of experience
        'rating' => 1.5,         // Patient rating
        'keyword_match' => 4.0,  // Direct keyword matching
    ];
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Main method to get doctor recommendations based on patient problem
     * 
     * @param string $patientProblem - Patient's problem description
     * @return array - Sorted list of doctors with similarity scores
     */
    public function getRecommendations($patientProblem) {
        if (empty(trim($patientProblem))) {
            return $this->getAllDoctors();
        }
        
        // Step 1: Extract keywords from patient problem
        $keywords = $this->extractKeywords($patientProblem);
        
        // Step 2: Identify matching specializations
        $specializationScores = $this->identifySpecializations($keywords);
        
        // Step 3: Get all active doctors
        $doctors = $this->getAllDoctors();
        
        // Step 4: Calculate cosine similarity for each doctor
        $recommendations = [];
        $primarySpecialization = !empty($specializationScores) ? array_key_first($specializationScores) : '';
        
        foreach ($doctors as $doctor) {
            // Create feature vectors
            $patientVector = $this->createPatientVector($keywords, $specializationScores);
            $doctorVector = $this->createDoctorVector($doctor, $primarySpecialization, $specializationScores);
            
            // Calculate cosine similarity
            $similarity = $this->calculateCosineSimilarity($patientVector, $doctorVector);
            
            $recommendations[] = [
                'doctor' => $doctor,
                'similarity' => $similarity,
                'matched_specialization' => $primarySpecialization,
                'keyword_matches' => $this->countKeywordMatches($doctor['specialisation'], $keywords)
            ];
        }
        
        // Step 5: Sort by similarity (highest first)
        usort($recommendations, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return $recommendations;
    }
    
    /**
     * Extract keywords from patient problem text
     * Normalizes text and splits into individual keywords
     * 
     * @param string $problem
     * @return array - List of keywords
     */
    private function extractKeywords($problem) {
        // Convert to lowercase
        $problem = strtolower($problem);
        
        // Remove punctuation and extra spaces
        $problem = preg_replace('/[^\w\s]/', ' ', $problem);
        $problem = preg_replace('/\s+/', ' ', $problem);
        
        // Split into words
        $words = explode(' ', trim($problem));
        
        // Filter out common stop words
        $stopWords = ['i', 'have', 'been', 'feeling', 'since', 'please', 'help', 'my', 'the', 'a', 'an', 'is', 'are', 'was', 'were', 'to', 'for', 'and', 'or', 'but', 'with', 'on', 'at', 'by', 'from'];
        
        $keywords = [];
        foreach ($words as $word) {
            if (!in_array($word, $stopWords) && strlen($word) > 2) {
                $keywords[] = $word;
            }
        }
        
        // Add bigrams (two-word combinations)
        for ($i = 0; $i < count($words) - 1; $i++) {
            $bigram = $words[$i] . ' ' . $words[$i + 1];
            if (!in_array($bigram, $stopWords)) {
                $keywords[] = $bigram;
            }
        }
        
        return $keywords;
    }
    
    /**
     * Identify medical specializations based on extracted keywords
     * Returns scores for each matched specialization
     * 
     * @param array $keywords
     * @return array - Specialization => score
     */
    private function identifySpecializations($keywords) {
        $scores = [];
        
        foreach ($keywords as $keyword) {
            if (isset($this->keywordToSpecialization[$keyword])) {
                $specialization = $this->keywordToSpecialization[$keyword];
                
                if (!isset($scores[$specialization])) {
                    $scores[$specialization] = 0;
                }
                $scores[$specialization] += $this->featureWeights['keyword_match'];
            }
        }
        
        // Sort by score descending
        arsort($scores);
        
        return $scores;
    }
    
    /**
     * Create feature vector for patient based on problem analysis
     * 
     * @param array $keywords
     * @param array $specializationScores
     * @return array - Patient feature vector
     */
    private function createPatientVector($keywords, $specializationScores) {
        $primarySpec = !empty($specializationScores) ? array_key_first($specializationScores) : '';
        
        return [
            'specialisation' => !empty($primarySpec) ? 1.0 : 0.3,
            'experience' => 0.5,  // Neutral experience preference
            'rating' => 0.5,      // Neutral rating preference
            'keyword_match' => count($keywords) > 0 ? 1.0 : 0.0
        ];
    }
    
    /**
     * Create feature vector for doctor
     * 
     * @param array $doctor - Doctor data from database
     * @param string $targetSpecialization - Primary matched specialization
     * @param array $specializationScores - All matched specializations
     * @return array - Doctor feature vector
     */
    private function createDoctorVector($doctor, $targetSpecialization, $specializationScores) {
        // Specialization match (binary: 1 if matches, 0 if not)
        $specMatch = ($doctor['specialisation'] === $targetSpecialization) ? 1.0 : 0.0;
        
        // Experience score (normalized: 0-1, max 20 years = 1.0)
        $experience = min($doctor['experience'] / 20, 1.0);
        
        // Rating score (normalized: 0-1, assuming max 5 stars)
        $rating = ($doctor['rating'] ?? 4.0) / 5.0;
        
        // Keyword match score based on specialization relevance
        $keywordMatch = 0;
        if (!empty($specializationScores) && $specMatch > 0) {
            $keywordMatch = min($specializationScores[$targetSpecialization] / 20, 1.0);
        }
        
        return [
            'specialisation' => $specMatch,
            'experience' => $experience,
            'rating' => $rating,
            'keyword_match' => $keywordMatch
        ];
    }
    
    /**
     * Calculate cosine similarity between two vectors
     * 
     * Formula: cos(θ) = (A · B) / (||A|| × ||B||)
     * 
     * @param array $vectorA - Patient vector
     * @param array $vectorB - Doctor vector
     * @return float - Similarity score (0 to 1)
     */
    private function calculateCosineSimilarity($vectorA, $vectorB) {
        // Apply weights to vectors
        $weightedA = [];
        $weightedB = [];
        
        foreach ($vectorA as $key => $value) {
            $weight = $this->featureWeights[$key] ?? 1.0;
            $weightedA[$key] = $value * $weight;
            $weightedB[$key] = $vectorB[$key] * $weight;
        }
        
        // Calculate dot product: A · B = Σ(ai * bi)
        $dotProduct = 0;
        foreach ($weightedA as $key => $value) {
            $dotProduct += $value * ($weightedB[$key] ?? 0);
        }
        
        // Calculate magnitude: ||A|| = √(Σ(ai²))
        $magnitudeA = 0;
        foreach ($weightedA as $value) {
            $magnitudeA += $value * $value;
        }
        $magnitudeA = sqrt($magnitudeA);
        
        // Calculate magnitude: ||B|| = √(Σ(bi²))
        $magnitudeB = 0;
        foreach ($weightedB as $value) {
            $magnitudeB += $value * $value;
        }
        $magnitudeB = sqrt($magnitudeB);
        
        // Handle edge cases
        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0;
        }
        
        // Calculate cosine similarity
        $similarity = $dotProduct / ($magnitudeA * $magnitudeB);
        
        // Ensure result is between 0 and 1
        return max(0, min(1, $similarity));
    }
    
    /**
     * Get all active doctors from database
     * 
     * @return array - List of doctors
     */
    private function getAllDoctors() {
        $stmt = $this->db->query("SELECT * FROM doctor WHERE status != 'deleted' ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count how many keywords match a specialization
     * 
     * @param string $specialization
     * @param array $keywords
     * @return int
     */
    private function countKeywordMatches($specialization, $keywords) {
        $count = 0;
        foreach ($keywords as $keyword) {
            if (isset($this->keywordToSpecialization[$keyword]) && 
                $this->keywordToSpecialization[$keyword] === $specialization) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Get detailed explanation of recommendation
     * Useful for debugging and transparency
     * 
     * @param array $recommendation
     * @return string
     */
    public function getRecommendationReason($recommendation) {
        $doctor = $recommendation['doctor'];
        $similarity = $recommendation['similarity'];
        $spec = $recommendation['matched_specialization'];
        
        $reasons = [];
        
        if ($recommendation['keyword_matches'] > 0) {
            $reasons[] = "Matched {$recommendation['keyword_matches']} keyword(s) to {$spec}";
        }
        
        if ($doctor['specialisation'] === $spec) {
            $reasons[] = "Specializes in {$spec}";
        }
        
        if ($doctor['experience'] >= 10) {
            $reasons[] = "{$doctor['experience']} years of experience";
        }
        
        if (($doctor['rating'] ?? 4) >= 4.5) {
            $reasons[] = "High patient rating";
        }
        
        $reasons[] = "Similarity score: " . round($similarity * 100) . "%";
        
        return implode('. ', $reasons);
    }
}

// AJAX handler for recommendations
if (isset($_GET['get_recommendations'])) {
    header('Content-Type: application/json');
    
    $problem = $_GET['problem'] ?? '';
    
    $recommender = new CosineSimilarityRecommendation();
    $recommendations = $recommender->getRecommendations($problem);
    
    echo json_encode([
        'success' => true,
        'count' => count($recommendations),
        'recommendations' => array_map(function($rec) use ($recommender) {
            return [
                'doctor' => $rec['doctor'],
                'similarity' => round($rec['similarity'], 4),
                'match_percentage' => round($rec['similarity'] * 100),
                'reason' => $recommender->getRecommendationReason($rec)
            ];
        }, $recommendations)
    ]);
    
    exit;
}
?>