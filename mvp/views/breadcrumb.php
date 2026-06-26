<?php
/**
 * Breadcrumb Navigation Component
 * Generates breadcrumb navigation based on current route
 */
function renderBreadcrumb($route, $params = []) {
    $breadcrumbs = [];
    
    // Always start with home/clients
    $breadcrumbs[] = [
        'label' => 'Clients',
        'url' => '?route=clients',
        'active' => false
    ];
    
    switch ($route) {
        case 'client':
            if (!empty($params['client'])) {
                $breadcrumbs[] = [
                    'label' => $params['client']['displayName'] ?? 'Client',
                    'url' => '?route=client&id=' . urlencode($params['client']['id']),
                    'active' => true
                ];
            }
            break;
            
        case 'project':
            if (!empty($params['project'])) {
                $project = $params['project'];
                $client = $params['client'] ?? null;
                
                if ($client) {
                    $breadcrumbs[] = [
                        'label' => $client['displayName'] ?? 'Client',
                        'url' => '?route=client&id=' . urlencode($client['id']),
                        'active' => false
                    ];
                }
                
                $breadcrumbs[] = [
                    'label' => $project['name'] ?? 'Project',
                    'url' => '?route=project&id=' . urlencode($project['id']),
                    'active' => true
                ];
            }
            break;
            
        case 'populate':
        case 'preview':
        case 'pdf-preview':
            if (!empty($params['projectDocument'])) {
                $projectDoc = $params['projectDocument'];
                $project = $params['project'] ?? null;
                $client = $params['client'] ?? null;
                
                if ($client) {
                    $breadcrumbs[] = [
                        'label' => $client['displayName'] ?? 'Client',
                        'url' => '?route=client&id=' . urlencode($client['id']),
                        'active' => false
                    ];
                }
                
                if ($project) {
                    $breadcrumbs[] = [
                        'label' => $project['name'] ?? 'Project',
                        'url' => '?route=project&id=' . urlencode($project['id']),
                        'active' => false
                    ];
                }
                
                $actionLabel = 'Document';
                switch ($route) {
                    case 'populate': $actionLabel = 'Populate Document'; break;
                    case 'preview': $actionLabel = 'Preview Document'; break;
                    case 'pdf-preview': $actionLabel = 'Field Mapping'; break;
                }
                
                $breadcrumbs[] = [
                    'label' => $actionLabel,
                    'url' => '',
                    'active' => true
                ];
            }
            break;
            
        case 'projects':
            $breadcrumbs[] = [
                'label' => 'All Projects',
                'url' => '?route=projects',
                'active' => true
            ];
            break;
            
        case 'templates':
            $breadcrumbs[] = [
                'label' => 'Templates',
                'url' => '?route=templates',
                'active' => true
            ];
            break;
            
        case 'support':
            $breadcrumbs[] = [
                'label' => 'Help & Support',
                'url' => '?route=support',
                'active' => true
            ];
            break;
            
        default:
            // For clients route, mark as active
            if ($route === 'clients') {
                $breadcrumbs[0]['active'] = true;
            }
            break;
    }
    
    // Render breadcrumbs
    echo '<nav class="breadcrumb">';
    echo '<ol class="breadcrumb-list">';
    
    foreach ($breadcrumbs as $index => $crumb) {
        echo '<li class="breadcrumb-item' . ($crumb['active'] ? ' active' : '') . '">';
        
        if ($crumb['active'] || empty($crumb['url'])) {
            echo '<span class="breadcrumb-text">' . htmlspecialchars($crumb['label']) . '</span>';
        } else {
            echo '<a href="' . htmlspecialchars($crumb['url']) . '" class="breadcrumb-link">' . htmlspecialchars($crumb['label']) . '</a>';
        }
        
        echo '</li>';
        
        // Add separator if not last item
        if ($index < count($breadcrumbs) - 1) {
            echo '<li class="breadcrumb-separator">›</li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
}
?>













