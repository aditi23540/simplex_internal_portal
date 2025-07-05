// assets/js/dashboard_charts.js

document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('loader');
    const content = document.getElementById('dashboard-content');

    // Fetch data from your backend PHP script
    const cacheBustURL = 'data_queries.php?cacheBust=' + new Date().getTime();

    fetch(cacheBustURL)
        .then(response => {
            if (!response.ok) { throw new Error(`Network response was not ok. Status: ${response.status}`); }
            return response.json();
        })
        .then(dashboardData => {
            if (dashboardData.error) { throw new Error(`PHP Error: ${dashboardData.php_error_message}`); }
            
            // Hide loader and show content
            if(loader) loader.classList.add('d-none');
            if(content) content.classList.remove('d-none');
            
            // Initialize the dashboard with the fetched data
            initializeDashboard(dashboardData);
        })
        .catch(error => {
            console.error('Failed to load or initialize dashboard:', error);
            const mainContainer = document.querySelector('.page-inner'); // Target the main template's content area
            mainContainer.innerHTML = `<div class="alert alert-danger m-5"><strong>Dashboard Failed to Load.</strong><br><strong>Error:</strong> ${error.message}</div>`;
        });
});

function initializeDashboard(dashboardData) {
    // --- SETUP & INITIALIZATION ---
    $('#dept-filter').select2({ theme: 'bootstrap-5', placeholder: 'Search Departments...', width: '100%'});
    $('#desg-filter').select2({ theme: 'bootstrap-5', placeholder: 'Search Designations...', width: '100%'});
    $('#dept-head-filter').select2({ theme: 'bootstrap-5', placeholder: 'Search Department Heads...', width: '100%'});
    
    // Populate filter dropdowns
    dashboardData.department_names.forEach(name => $('#dept-filter').append(new Option(name, name, false, false)));
    dashboardData.designation_names.forEach(name => $('#desg-filter').append(new Option(name, name, false, false)));
    dashboardData.department_head_names.forEach(name => $('#dept-head-filter').append(new Option(name, name, false, false)));

    const masterDepartmentData = dashboardData.department_breakdown;
    const masterDesignationData = dashboardData.designation_breakdown;
    const masterDeptHeadData = dashboardData.department_head_breakdown;

    // --- RENDER ALL CHARTS & KPIs ---
    function renderAll() {
        $('#kpi-active-employees').text(dashboardData.active_headcount);
        $('#kpi-inactive-employees').text(dashboardData.inactive_headcount);
        
        safeRender('#dept-breakdown-chart', masterDepartmentData.slice(0, 15), createBarChart, { hasInfoText: true });
        safeRender('#designation-breakdown-chart', masterDesignationData.slice(0, 15), createBarChart, {});
        safeRender('#unit-breakdown-chart', dashboardData.unit_breakdown.slice(0, 15), createBarChart, {});
        safeRender('#decade-chart', dashboardData.hiring_by_decade, createBarChart, {});
        safeRender('#dept-head-breakdown-chart', masterDeptHeadData.slice(0, 15), createBarChart, { hasInfoText: true });
        
        safeRender('#headcount-trend-chart', dashboardData.headcount_trend, createLineChart);
        safeRender('#gender-chart', dashboardData.gender_distribution, createDonutChart);
        safeRender('#attendance-policy-chart', dashboardData.attendance_policy_distribution, createDonutChart);
        
        populateDeptHeadAccordion(dashboardData.department_head_details);
    }
    renderAll();
    
    // --- EVENT LISTENERS ---
    $('#dept-filter').on('change', () => {
        const selected = $('#dept-filter').val();
        const data = (selected && selected.length > 0) ? masterDepartmentData.filter(d => selected.includes(d.name)) : masterDepartmentData.slice(0, 15);
        safeRender('#dept-breakdown-chart', data, createBarChart, { hasInfoText: true });
    });
    $('#show-top15-dept-btn').on('click', () => { $('#dept-filter').val(null).trigger('change'); });
    $('#show-all-dept-btn').on('click', () => { safeRender('#dept-breakdown-chart', masterDepartmentData, createBarChart, { hasInfoText: true }); });

    $('#desg-filter').on('change', () => {
        const selected = $('#desg-filter').val();
        const data = (selected && selected.length > 0) ? masterDesignationData.filter(d => selected.includes(d.name)) : masterDesignationData.slice(0, 15);
        safeRender('#designation-breakdown-chart', data, createBarChart, {});
    });
    $('#show-top15-desg-btn').on('click', () => { $('#desg-filter').val(null).trigger('change'); });
    $('#show-all-desg-btn').on('click', () => { safeRender('#designation-breakdown-chart', masterDesignationData, createBarChart, {}); });

    $('#dept-head-filter').on('change', () => {
        const selected = $('#dept-head-filter').val();
        const data = (selected && selected.length > 0) ? masterDeptHeadData.filter(d => selected.includes(d.name)) : masterDeptHeadData.slice(0, 15);
        safeRender('#dept-head-breakdown-chart', data, createBarChart, { hasInfoText: true });
    });
    $('#show-top15-head-btn').on('click', () => { $('#dept-head-filter').val(null).trigger('change'); });
    $('#show-all-head-btn').on('click', () => { safeRender('#dept-head-breakdown-chart', masterDeptHeadData, createBarChart, { hasInfoText: true });});
}

function safeRender(selector, data, chartFunction, options = {}) {
    const container = d3.select(selector);
    container.html('');
    if (!data || data.length === 0) {
        container.html('<p class="text-center text-muted p-5">No data available for this view.</p>');
        return;
    }
    chartFunction(selector, data, options);
}


// --- ACCORDION POPULATION FUNCTION ---
function populateDeptHeadAccordion(details) {
    const accordionContainer = $('#dept-head-accordion-container');
    accordionContainer.html('<div class="accordion" id="dept-head-accordion"></div>');
    const accordionParent = $('#dept-head-accordion');
    if (!details || details.length === 0) {
        accordionParent.html('<p class="text-center text-muted p-3">No department head details available.</p>');
        return;
    }
    details.forEach((head, index) => {
        const departments = head.departments.join(', ');
        let employeeListHtml = `<table class="table table-sm table-sticky-header table-hover mt-2"><thead><tr><th>Name</th><th>Designation</th></tr></thead><tbody>`;
        head.employees.forEach(emp => {
            employeeListHtml += `<tr><td>${emp.name}</td><td>${emp.designation}</td></tr>`;
        });
        employeeListHtml += '</tbody></table>';
        const accordionItem = `
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-${index}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${index}">
                        <strong>${head.name}</strong> <span class="badge bg-primary rounded-pill ms-3">${head.employee_count} Employees</span>
                    </button>
                </h2>
                <div id="collapse-${index}" class="accordion-collapse collapse" data-bs-parent="#dept-head-accordion">
                    <div class="accordion-body">
                        <strong>Manages Departments:</strong> ${departments}
                        <div class="table-container-scrollable mt-2">
                            ${employeeListHtml}
                        </div>
                    </div>
                </div>
            </div>`;
        accordionParent.append(accordionItem);
    });
}


// --- D3 CHART DRAWING FUNCTIONS ---
function createBarChart(selector, data, options = {}) {
    const container = d3.select(selector);
    const margin = { top: (options.hasInfoText ? 40 : 30), right: 20, bottom: 120, left: 50 };
    const width = container.node().getBoundingClientRect().width - margin.left - margin.right;
    const height = 400 - margin.top - margin.bottom;
    const svg = container.append('svg').attr('width', width + margin.left + margin.right).attr('height', height + margin.top + margin.bottom).append('g').attr('transform', `translate(${margin.left},${margin.top})`);
    
    if (options.hasInfoText) {
        svg.append('text').attr('class', 'info-text').attr('x', width / 2).attr('y', -15).attr('text-anchor', 'middle')
            .style('font-size', '1.1rem').style('font-weight', 'bold').style('fill', '#343a40')
            .text('Hover over an item');
    }

    const x = d3.scaleBand().range([0, width]).domain(data.map(d => d.name)).padding(0.3);
    const y = d3.scaleLinear().range([height, 0]).domain([0, d3.max(data, d => d.value)]);

    svg.append('g').attr('transform', `translate(0,${height})`).call(d3.axisBottom(x)).selectAll('text').attr('transform', 'translate(-10,0)rotate(-45)').style('text-anchor', 'end');
    svg.append('g').call(d3.axisLeft(y));

    const bars = svg.selectAll('.bar').data(data).enter().append('rect')
        .attr('class', 'bar').attr('x', d => x(d.name)).attr('width', x.bandwidth()).attr('y', height).attr('height', 0)
        .attr('fill', '#69b3a2').style('cursor', 'pointer');
        
    if (options.hasInfoText) {
        bars.on('mouseover.infotext', function(event, d) {
            svg.select('.info-text').text(`${d.name}: ${d.value}`);
        }).on('mouseout.infotext', function() {
            svg.select('.info-text').text('Hover over an item');
        });
    }
        
    bars.transition().duration(800).attr('y', d => y(d.value)).attr('height', d => height - y(d.value));

    svg.selectAll('.bar-label').data(data).enter().append('text')
        .attr('class', 'bar-label').attr('x', d => x(d.name) + x.bandwidth() / 2).attr('y', d => y(d.value) - 5)
        .attr('text-anchor', 'middle').style('font-size', '12px').style('font-weight', 'bold').style('fill', '#333')
        .text(d => d.value);
}
    
function createDonutChart(selector, data) {
    const container = d3.select(selector);
    const legendContainer = d3.select(selector + '-legend');
    container.html('');
    if(legendContainer) legendContainer.html('');

    const height = 300;
    const width = container.node().getBoundingClientRect().width;
    const radius = Math.min(width, height) / 2;

    const svg = container.append('svg').attr('width', width).attr('height', height)
        .append('g').attr('transform', `translate(${width / 2}, ${height / 2})`);
        
    const color = d3.scaleOrdinal().domain(data.map(d => d.name)).range(d3.schemeTableau10);
    
    const pie = d3.pie().value(d => d.value).sort(null);
    const data_ready = pie(data);
    const arc = d3.arc().innerRadius(radius * 0.6).outerRadius(radius);

    svg.selectAll('path').data(data_ready).enter().append('path')
        .attr('d', arc).attr('fill', d => color(d.data.name)).attr('stroke', 'white').style('stroke-width', '2px');
        
    data.forEach(d => {
        const legendItem = legendContainer.append('div').attr('class', 'legend-item');
        legendItem.append('div').attr('class', 'legend-swatch').style('background-color', color(d.name));
        const labelBlock = legendItem.append('div');
        labelBlock.append('div').attr('class', 'legend-label-name').text(d.name);
        labelBlock.append('div').attr('class', 'legend-label-value').text(`Count: ${d.value}`);
    });
}

function createLineChart(selector, data) {
    const container = d3.select(selector);
    const margin = { top: 30, right: 30, bottom: 80, left: 60 };
    const width = container.node().getBoundingClientRect().width - margin.left - margin.right;
    const height = 400 - margin.top - margin.bottom;
    const svg = container.append('svg').attr('width', width + margin.left + margin.right).attr('height', height + margin.top + margin.bottom).append('g').attr('transform', `translate(${margin.left},${margin.top})`);
    
    const x = d3.scaleBand().range([0, width]).domain(data.map(d => d.name)).padding(0.1);
    const y = d3.scaleLinear().range([height, 0]).domain([0, d3.max(data, d => d.value)]);

    svg.append('g').attr('transform', `translate(0,${height})`).call(d3.axisBottom(x)).selectAll('text').attr('transform', 'rotate(-45)').style('text-anchor', 'end');
    svg.append('g').call(d3.axisLeft(y));
    
    svg.append('path').datum(data).attr('fill', 'none').attr('stroke', '#0d6efd').attr('stroke-width', 2.5)
       .attr('d', d3.line().x(d => x(d.name) + x.bandwidth()/2).y(d => y(d.value)));
       
    svg.selectAll("circle").data(data).enter().append("circle")
        .attr("cx", d => x(d.name) + x.bandwidth()/2).attr("cy", d => y(d.value)).attr("r", 5).attr("fill", "#0d6efd");
        
    svg.selectAll('.line-label').data(data).enter().append('text')
        .attr('class', 'line-label').attr('x', d => x(d.name) + x.bandwidth() / 2)
        .attr('y', d => y(d.value) - 12).attr('text-anchor', 'middle')
        .style('font-size', '12px').style('font-weight', 'bold').style('fill', '#333')
        .text(d => d.value);
}