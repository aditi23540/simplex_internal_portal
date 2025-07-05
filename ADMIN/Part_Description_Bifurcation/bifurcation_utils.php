<?php
// bifurcation_utils.php

if (!defined('BIFURCATION_UTILS_LOADED')) {
    define('BIFURCATION_UTILS_LOADED', true);

    if (!function_exists('getBifurcationType')) {
        function getBifurcationType(string $material_no): string {
            if (empty($material_no) || !is_string($material_no) || strlen($material_no) < 2) {
                return "Other";
            }
            $prefix = substr($material_no, 0, 2);
            $screw_prefixes = ['20', '13', '17', '19', '21', '24', '26'];
            $plate_prefix   = '31'; 
            $pipe_prefix    = '25';

            if (in_array($prefix, $screw_prefixes)) return "Screw Nut Bolt";
            elseif ($prefix === $plate_prefix) return "Plate";
            elseif ($prefix === $pipe_prefix) return "Pipes";
            return "Other";
        }
    }

    if (!function_exists('extractAttributesFromDescription')) {
        function extractAttributesFromDescription(string $description, string $bifurcationType): array {
            $extracted = [
                'diameter' => null, 'length' => null, 'class' => null,
                'nb' => null, 'od' => null, 'thickness_pipe' => null,
                'thickness_plate' => null, 'grade' => null,
                'standard_extracted' => null, 'uom_extracted' => null,
            ];
            if (empty($description) || !is_string($description)) { return $extracted;}

            $original_desc = $description; 
            $work_desc = strtoupper($description); 
            $work_desc = preg_replace('/\s*(X|×|\*)\s*/', ' X ', $work_desc);
            $work_desc = trim(preg_replace('/\s+/', ' ', $work_desc));

            $standards_found = [];
            $std_patterns = [
                '\bIS\s*:\s*\d+(?:[\w.-]*)?(?:\s*PART\s*\d+[\w-]*)?(?:\s*GR(?:ADE)?\s*[A-Z0-9.-]+)?', 
                '\bIS\s+\d+(?:\s*[\/\w.:()-]+)?(?:\s*PART\s*\d+[\w-]*)?(?:\s*GR(?:ADE)?\s*[A-Z0-9.-]+)?', 
                '\bASTM\s*[A-Z]\s*\d+(?:[A-Z])?(?:M)?(?:[\s\/\w-]+)?',       
                '\bASME\s*[A-Z]+\s*\d+\.?\d*[\w-]*',                                        
                '\bDIN\s*(?:EN\s*)?(?:ISO\s*)?\d+[\w-]*',                 
                '\bEN\s*\d+(?:-\d+)?(?::\d{4})?[\w-]*',                       
                '\bISO\s*\d+(?:-\d+)?(?::\d{4})?[\w-]*',                       
                '\bBS\s*\d+(?:-\d+)?[\w-]*',                                   
            ];
            foreach ($std_patterns as $pattern) {
                if (preg_match_all('/' . $pattern . '\b/i', $original_desc, $matches)) {
                    foreach ($matches[0] as $match) { $standards_found[] = trim($match); }
                }
            }
            if (!empty($standards_found)) { $extracted['standard_extracted'] = implode(', ', array_unique($standards_found)); }

            if (preg_match('/\b(PCS|PIECES)\b/i', $work_desc, $m)) $extracted['uom_extracted'] = 'Pcs';
            elseif (preg_match('/\b(NOS|NO\.?|NUMBER|EACH|EA)\b/i', $work_desc, $m)) $extracted['uom_extracted'] = 'NOS';
            elseif (preg_match('/\b(KGS?|KILOGRAMS?)\b/i', $work_desc, $m)) $extracted['uom_extracted'] = 'KG';
            elseif (preg_match('/\b(MTRS?|METERS?|METRES?)\b/i', $work_desc, $m)) $extracted['uom_extracted'] = 'M';
            elseif (preg_match('/\b(?<![A-Z0-9])M\b(?![A-Z0-9])/i', $work_desc, $m_uom) && !preg_match('/\bM\d+/i', $work_desc)) {
                $extracted['uom_extracted'] = 'M';
            }

            if ($bifurcationType === "Screw Nut Bolt") {
                if (preg_match('/\b(M\s*\d+(?:\.\d+)?)\b/i', $work_desc, $m)) $extracted['diameter'] = str_replace(' ', '', $m[1]);
                elseif (preg_match('/DIA(?:METER)?\s*([A-Z0-9\/\."\'\-]+)/i', $work_desc, $m)) $extracted['diameter'] = trim($m[1]);
                
                if (!empty($extracted['diameter']) && preg_match('/' . preg_quote(str_replace(' ','',$extracted['diameter']), '/') . '\s*X\s*(\d+(?:\.\d+)?)\s*(MM)?/i', $work_desc, $m)) { $extracted['length'] = $m[1]; }
                elseif (preg_match('/X\s*(\d+(?:\.\d+)?)\s*(MM)?/i', $work_desc, $m)) { $extracted['length'] = $m[1]; }
                elseif (preg_match('/\b(\d+(?:\.\d+)?)\s*MM\s*(?:LG|LONG)/i', $work_desc, $m)) { $extracted['length'] = $m[1]; }
                elseif (preg_match('/\bL\s*[-:]?\s*(\d+(?:\.\d+)?)/i', $work_desc, $m)) { $extracted['length'] = $m[1]; }
                
                if (preg_match('/\b(?:CL|CLASS|GRADE|GR)\s*([A-Z0-9\.-]+(?:\s*[A-Z0-9\.-]+)?)\b/i', $work_desc, $m)) $extracted['class'] = trim($m[1]);
                elseif (preg_match('/\b(A2-\d+|A4-\d+|TP\d+[A-Z]*|8\.8|10\.9|12\.9|4\.6|H\.?T\.?)\b/i', $work_desc, $m)) $extracted['class'] = trim($m[1]);

            } elseif ($bifurcationType === "Pipes") {
                if (preg_match('/(\d+(?:\.\d+)?|[\d\/"]+)\s*NB\b/i', $work_desc, $m)) $extracted['nb'] = trim($m[1]);
                elseif (preg_match('/\bNB\s*(\d+(?:\.\d+)?|[\d\/"]+)/i', $work_desc, $m)) $extracted['nb'] = trim($m[1]);
                if (preg_match('/\bOD\s*(\d+(?:\.\d+)?)\s*(MM)?/i', $work_desc, $m)) $extracted['od'] = trim($m[1]);

                if (!empty($extracted['od']) && preg_match('/OD\s*[\d\.]+\s*(?:MM)?\s*X\s*(\d+(?:\.\d+)?)\s*(MM)?/i', $work_desc, $m)) { $extracted['thickness_pipe'] = $m[1];}
                elseif (preg_match('/\b(\d+(?:\.\d+)?)\s*MM\s*(?:THK|THICKNESS)\b/i', $work_desc, $m)) { $extracted['thickness_pipe'] = $m[1];}
                elseif (preg_match('/\b(?:THK|THICKNESS)\s*(\d+(?:\.\d+)?)\s*(MM)?/i', $work_desc, $m)) { $extracted['thickness_pipe'] = $m[1];}
                elseif (preg_match('/\bSCH(?:EDULE)?\s*([\w\d\.]+)\b/i', $work_desc, $m_sch)) { 
                    $extracted['thickness_pipe'] = 'SCH ' . trim($m_sch[1]); 
                    if(is_null($extracted['class'])) $extracted['class'] = $extracted['thickness_pipe'];
                }

            } elseif ($bifurcationType === "Plate") {
                if (preg_match('/(?:PLATE\s*)?(\d+(?:\.\d+)?)\s*MM(?:\s*THK)?/i', $work_desc, $m)) $extracted['thickness_plate'] = $m[1];
                elseif (preg_match('/(\d+(?:\.\d+)?)\s*(?:MM)?\s*THK/i', $work_desc, $m)) $extracted['thickness_plate'] = $m[1];

                if (preg_match('/\b(?:GRADE|GR)\s*([A-Z0-9\s\/-]+[A-Z0-9])\b/i', $work_desc, $m)) { $extracted['grade'] = trim($m[1]); }
                elseif (preg_match('/\b(HARDOX\s*\d+[A-Z\d]*|SAILHARD|C\d{2,3}|E\d{3}[A-Z\d]*|S\d{3}[A-Z\d.]*|A\d{2,3}[A-Z\d]*|P\d{2,3}[A-Z\d]*)\b/i', $work_desc, $m_grade)) {
                    $is_part_of_standard = false; 
                    if (!empty($extracted['standard_extracted']) && stripos(strtoupper($extracted['standard_extracted']), strtoupper($m_grade[1])) !== false) {
                        $is_part_of_standard = true;
                    }
                    if (!$is_part_of_standard) { 
                        $extracted['grade'] = trim($m_grade[1]); 
                    } elseif (is_null($extracted['grade']) && $is_part_of_standard && count($standards_found ?? []) == 1){ 
                        if(preg_match('/\b(E\d{3}[A-Z\d]*|S\d{3}[A-Z\d.]*)\b/i', $standards_found[0], $grade_in_std_match)) {
                            $extracted['grade'] = $grade_in_std_match[0];
                        }
                    }
                }
            }

            if (!empty($extracted['standard_extracted']) && is_null($extracted['grade'])) {
                if (preg_match('/(IS\s*\d+(?::\d+)?)\s*(E\d{3}[A-Z\d]*|GR[\s.-]*[A-Z\d.-]+)/i', $extracted['standard_extracted'], $std_grade_parts)) { 
                    $extracted['standard_extracted'] = trim($std_grade_parts[1]); 
                    $extracted['grade'] = trim($std_grade_parts[2]); 
                }
            }
            
            foreach (['diameter', 'length', 'nb', 'od', 'thickness_pipe', 'thickness_plate'] as $field) {
                if (!empty($extracted[$field]) && is_string($extracted[$field])) { 
                    $extracted[$field] = preg_replace('/\s*MM$/i', '', $extracted[$field]); 
                }
            }

            if(!empty($standards_found) && is_null($extracted['grade'])){
                foreach($standards_found as $std_item){ 
                    if (preg_match('/\b(E\d{3}[A-Z\d]*|S\d{3}[A-Z\d.]*|C\d{2,3}|TP\d+[A-Z]*)\b/i', $std_item, $grade_match_in_std)){ 
                        $potential_grade = $grade_match_in_std[0]; 
                        if(!is_numeric($potential_grade)){ 
                            $extracted['grade'] = $potential_grade; 
                            break; 
                        }
                    }
                }
            }
            return $extracted;
        }
    }
}
?>