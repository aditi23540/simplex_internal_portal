<?php
// config.php for Part Description Bifurcation (Corrected Attributes for Plate & Pipes)
$columnConfigs = [
    'initial' => [
        'displayMap' => [
            'id' => 'ID', 'material_no' => 'Material No', 'material_description' => 'Description'
        ],
        'relevantFilters' => ['plant'] 
    ],
    'Pipes' => [
        'displayMap' => [ // REMOVED: grade, length, class
            'id' => 'ID', 'material_no' => 'Material No', 'material_description' => 'Description', 'plant' => 'Plant',
            'nb' => 'NB', 'od' => 'OD', 'thickness' => 'Thickness',
            'standard' => 'Standard', 'uom' => 'UOM' // UOM is fixed to 'M'
        ],
        // REMOVED: grade, length, class from relevantFilters
        'relevantFilters' => ['plant', 'nb', 'od', 'thickness', 'standard'] 
    ],
    'Plate' => [
        'displayMap' => [ // REMOVED: length
            'id' => 'ID', 'material_no' => 'Material No', 'material_description' => 'Description', 'plant' => 'Plant',
            'thickness' => 'Thickness', 
            'grade' => 'Grade', 'standard' => 'Standard', 'uom' => 'UOM' // UOM is fixed to 'Kg'
        ],
        // REMOVED: length from relevantFilters
        'relevantFilters' => ['plant', 'thickness', 'grade', 'standard'] 
    ],
    'Screw Nut Bolt' => [
        'displayMap' => [
            'id' => 'ID', 'material_no' => 'Material No', 'material_description' => 'Description', 'plant' => 'Plant',
            'diameter' => 'Diameter', 'length' => 'Length', 'class' => 'Class',
            'standard' => 'Standard', 'uom' => 'UOM' // UOM is fixed to 'Pcs'
        ],
        'relevantFilters' => ['plant', 'diameter', 'length', 'class', 'standard']
    ],
    'Other' => [
        'displayMap' => [ 
            'id' => 'ID', 'material_no' => 'Material No', 
            'material_description' => 'Description', 'plant' => 'Plant',
            'type' => 'Determined Type'
            // You can add other general parsed fields here if desired for 'Other' type display, e.g.:
            // 'standard' => 'Standard', 'uom' => 'UOM (Parsed)', 'grade' => 'Grade'
        ],
        // For "Other", only the always-visible filters + general parsed ones that make sense.
        'relevantFilters' => ['plant', 'standard', 'uom', 'grade'] // Example, adjust as needed for 'Other'
    ]
];
?>