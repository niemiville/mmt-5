<?php
namespace App\Controller;

use App\Controller\AppController;
use Highcharts\Controller\Component\HighchartsComponent;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

class ChartsController extends AppController
{
    public $name = 'Charts';
    public $helpers = ['Highcharts.Highcharts'];
    public $uses = array();
    
    public function initialize() {
        parent::initialize();
        $this->loadComponent('Highcharts.Highcharts');
    }
    
    public function index() {
        // When the chart limits are updated this is where they are saved
        if ($this->request->is('post')) {
            $data = $this->request->data;
            $chart_limits['weekmin'] = $data['weekmin'];
            $chart_limits['weekmax'] = $data['weekmax'];
            $chart_limits['yearmin'] = $data['yearmin'];
            $chart_limits['yearmax'] = $data['yearmax'];
			
            $this->request->session()->write('chart_limits', $chart_limits);
            // refreshin the page to apply the new limits
            $page = $_SERVER['PHP_SELF'];
        }
        // Set the stock limits for the chart limits
        // They are only set once, if the "chart_limits" cookie is not in the session
        if(!$this->request->session()->check('chart_limits')){
            $time = Time::now();
            // show last year, current year and next year
            $chart_limits['weekmin'] = 1;
            $chart_limits['weekmax'] =  52;
            $chart_limits['yearmin'] = $time->year - 1;
            $chart_limits['yearmax'] = $time->year;
            
            $this->request->session()->write('chart_limits', $chart_limits);
        }
        // Loadin the limits to a variable
        $chart_limits = $this->request->session()->read('chart_limits');
        // The ID of the currently selected project
        $project_id = $this->request->session()->read('selected_project')['id'];
        
        // Get the chart objects for the charts
        // these objects come from functions in this controller
        $phaseChart = $this->phaseChart();
        $reqChart = $this->reqChart();
        $commitChart = $this->commitChart();
        $testcaseChart = $this->testcaseChart();
        $hoursChart = $this->hoursChart();
        $totalhourChart = $this->totalhourChart();
        $hoursPerWeekChart = $this->hoursPerWeekChart();
        $reqPercentChart = $this->reqPercentChart();
        $risksProbChart = $this->risksProbChart();
        $risksImpactChart = $this->risksImpactChart();
        $risksCombinedChart = $this->risksCombinedChart();
        $derivedChart = $this->derivedChart();
        $testiChart = $this->testiChart();
        
        // Get all the data for the charts, based on the chartlimits
        // Fuctions in "ChartsTable.php"
        $weeklyreports = $this->Charts->reports($project_id, $chart_limits['weekmin'], $chart_limits['weekmax'], $chart_limits['yearmin'], $chart_limits['yearmax']);
        $phaseData = $this->Charts->phaseAreaData($weeklyreports['id']);
        $reqData = $this->Charts->reqColumnData($weeklyreports['id']);
        $commitData = $this->Charts->commitAreaData($weeklyreports['id']);
        $testcaseData = $this->Charts->testcaseAreaData($weeklyreports['id']);
        $hoursData = $this->Charts->hoursData($project_id);
        $hoursperweekData = $this->Charts->hoursPerWeekData($project_id, $weeklyreports['id'], $weeklyreports['weeks']);
        $totalhourData = $this->Charts->totalhourLineData($project_id, $weeklyreports['id'], $weeklyreports['weeks']);
        $riskData = $this->Charts->riskData($weeklyreports['id'], $project_id);
        $testiData = $this->Charts->testiData($weeklyreports['id'], $weeklyreports['weeks']);
        
        // Insert the data in to the charts, one by one
        // phaseChart
        $phaseChart->xAxis->categories = $weeklyreports['weeks'];
        $phaseChart->series[] = array(
            'name' => 'Total phases planned',
            'data' => $phaseData['phaseTotal']
        );
        $phaseChart->series[] = array(
            'name' => 'Phase',
            'data' => $phaseData['phase']
        );
        
        // reqChart
        $reqChart->xAxis->categories = $weeklyreports['weeks'];
        $reqChart->series[] = array(
            'name' => 'Product Backlog',
            'data' => $reqData['new']
        );
        $reqChart->series[] = array(
            'name' => 'Sprint Backlog',
            'data' => $reqData['inprogress']
        );
        $reqChart->series[] = array(
            'name' => 'Done',
            'data' => $reqData['closed']
        );
        $reqChart->series[] = array(
            'name' => 'Rejected',
            'data' => $reqData['rejected']
        );
        
        // commitChart
        $commitChart->xAxis->categories = $weeklyreports['weeks'];    
        $commitChart->series[] = array(
            'name' => 'commits',
            'data' => $commitData['commits']
        );
        // testcaseChart
        $testcaseChart->xAxis->categories = $weeklyreports['weeks'];
        $testcaseChart->series[] = array(
            'name' => 'Total test cases',
            'data' => $testcaseData['testsTotal']
        );
        $testcaseChart->series[] = array(
            'name' => 'Passed test cases',
            'data' => $testcaseData['testsPassed']
        );
        // hoursChart
        $hoursChart->series[] = array(
            'name' => 'Management',
            'data' => array(
                $hoursData['management'],
                $hoursData['code'],
                $hoursData['document'],
                $hoursData['study'],
                $hoursData['other']
            )
        );
        
        // weeklyhourChart 
        $totalhourChart->xAxis->categories = $weeklyreports['weeks'];    
        $totalhourChart->series[] = array(
            'name' => 'total hours',
            'data' => $totalhourData
        );
        
        //workinghours per week  
        $hoursPerWeekChart->xAxis->categories = $weeklyreports['weeks'];
        $hoursPerWeekChart->series[] = array(
            'name' => 'Working hours per week',
            'data' => $hoursperweekData
        );
        
        // reqPercentChart
        $reqPercentChart->xAxis->categories = $weeklyreports['weeks'];
        $reqPercentChart->series[] = array(
            'name' => 'Product Backlog',
            'data' => $reqData['new']
        );
        $reqPercentChart->series[] = array(
            'name' => 'Sprint Backlog',
            'data' => $reqData['inprogress']
        );
        $reqPercentChart->series[] = array(
            'name' => 'Done',
            'data' => $reqData['closed']
        );
        $reqPercentChart->series[] = array(
            'name' => 'Rejected',
            'data' => $reqData['rejected']
        );
        
        // risksProbChart
        $risksProbChart->xAxis->categories = $weeklyreports['weeks'];
        
        foreach ($riskData as $risk){
            
            $risksProbChart->series[] = array(
                'name' => $risk['name'],
                'data' => $risk['probability']
            );
        
        }
        
        
        // risksImpactChart
        $risksImpactChart->xAxis->categories = $weeklyreports['weeks'];
        
        foreach ($riskData as $risk){
            
            $risksImpactChart->series[] = array(
                'name' => $risk['name'],
                'data' => $risk['impact']
            );
        
        }
        
        
        // risksCombinedChart
        $risksCombinedChart->xAxis->categories = $weeklyreports['weeks'];
        
        foreach ($riskData as $risk){
            
            $risksCombinedChart->series[] = array(
                'name' => $risk['name'],
                'data' => $risk['combined']
            );
        
        }     
        
              
        // chart for derived metrics
        $derivedChart->xAxis->categories = $weeklyreports['weeks'];
        $derivedChart->series[] = array(
            'name' => 'Total test cases',
            'data' => $testcaseData['testsTotal']
        );
        $derivedChart->series[] = array(
            'name' => 'Passed test cases',
            'data' => $testcaseData['testsPassed']
        );


        // TESTICHART
        $testiChart->xAxis->categories = $weeklyreports['weeks'];    
        $testiChart->series[] = array();
        foreach($testiData as $project_data) {
            $project_line = array(
                'data' => $project_data
            );
            array_push($testiChart->series['data'], 'data');
        }
        $this->Flash->success($testiChart->series['data']);
        


        // This sets the charts visible in the actual charts page "Charts/index.php"
        $this->set(compact('phaseChart', 'reqChart', 'commitChart', 'testcaseChart', 'hoursChart', 'totalhourChart', 'hoursPerWeekChart', 'reqPercentChart', 'risksProbChart', 
            'risksImpactChart', 'risksCombinedChart', 'derivedChart', 'testiChart'));

    }
    // All the following functions are similar
    // They create a custom chart object and return it
    // Unfortunately the functions have to be in the controller, 
    // because the chart objects cannot be created outside of the controller
    
    /* 12.3.2016: Total renovation of the charts:
     * - slight change to looks, alignments fixed
     *   -> now all charts have the same appearance and size
     * - labels on axes fixed
     * - commit chart changed from area to column diagram
     * - working hour chart makes more sense now
     *   -> and the numbers on columns are no longer blurred
     * - requirements charts under one header (with subheaders)
     * Requirement ID: 7 (Andy)
     */
    
    public function phaseChart() {
        $myChart = $this->Highcharts->createChart();
        $myChart->chart->renderTo = 'phasewrapper';
        $myChart->chart->type = 'area';
        $myChart->title = array(
            'text' => 'Phases',
            'y' => 20,
            'align' => 'center',
            'styleFont' => '18px Metrophobic, Arial, sans-serif',
            'styleColor' => '#0099ff',
        );
        // body of the chart
        $myChart->chart->width =  800;
        $myChart->chart->height = 500;
        /* (This part is removed from other functions. Kept here as backup.)
         $myChart->chart->marginTop = 60;
         $myChart->chart->marginLeft = 90;
         $myChart->chart->marginRight = 30;
         $myChart->chart->marginBottom = 110;
         $myChart->chart->spacingRight = 10;
         $myChart->chart->spacingBottom = 15;
         $myChart->chart->spacingLeft = 0;
         */
        // $myChart->chart->alignTicks = FALSE;
        $myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
        $myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
        // legend below the charts
        $myChart->legend->itemStyle = array('color' => '#222');
        $myChart->legend->backgroundColor->linearGradient = array(0, 0, 0, 25);
        $myChart->legend->backgroundColor->stops = array(array(0, 'rgb(217, 217, 217)'), array(1, 'rgb(255, 255, 255)'));
        // labels to describe the content of axes
        $myChart->xAxis->title->text = 'Week number';
        $myChart->yAxis->title->text = 'Total number of phases';
        
        // tooltips for the plotted graphs
        $myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return this.series.name +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
        $myChart->plotOptions->area->marker->enabled = false;;
        
        return $myChart;
    }
    
    public function reqChart() {
        $myChart = $this->Highcharts->createChart();
        $myChart->chart->renderTo = 'reqwrapper';
        $myChart->chart->type = 'column';
        
        $myChart->title = array(
        		'text' => 'Requirements',
        		'y' => 20,
        		'align' => 'center',
        		'styleFont' => '18px Metrophobic, Arial, sans-serif',
        		'styleColor' => '#0099ff',
        );
        $myChart->subtitle->text = 'in numbers';
        // body of the chart
        $myChart->chart->width =  800;
        $myChart->chart->height = 500;

        // $myChart->chart->alignTicks = FALSE;
        $myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
        $myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
        
        // legend below the chart
        $myChart->legend->enabled = true;
        $myChart->legend->layout = 'horizontal';
        $myChart->legend->align = 'center';
        $myChart->legend->verticalAlign  = 'bottom';
        $myChart->legend->itemStyle = array('color' => '#222');
        $myChart->legend->backgroundColor->linearGradient = array(0, 0, 0, 25);
        $myChart->legend->backgroundColor->stops = array(array(0, 'rgb(217, 217, 217)'), array(1, 'rgb(255, 255, 255)'));
        // labels to describe the content of axes
        $myChart->xAxis->title->text = 'Week number';
        $myChart->yAxis->title->text = 'Total number of requirements';
        
        // tooltips etc
        $myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
            return this.y;}");
        $myChart->plotOptions->column->pointPadding = 0.2;
        $myChart->plotOptions->column->borderWidth = 0;
        return $myChart;
    }
    
    public function reqPercentChart() {
    	$myChart = $this->Highcharts->createChart();
    	$myChart->chart->renderTo = "reqpercentwrapper";
    	$myChart->chart->type = "column";
    	$myChart->plotOptions->column->stacking = "percent";
    	
    	$myChart->title = array(
        		'text' => 'Requirements',
        		'y' => 20,
        		'align' => 'center',
        		'styleFont' => '18px Metrophobic, Arial, sans-serif',
        		'styleColor' => '#0099ff',
        );
    	$myChart->subtitle->text = 'in %';
    	
    	// body of the chart
    	$myChart->chart->width =  800;
    	$myChart->chart->height = 500;

    	// $myChart->chart->alignTicks = FALSE;
    	$myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
    	$myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
    	// legend below the charts
    	$myChart->legend->itemStyle = array('color' => '#222');
    	$myChart->legend->backgroundColor->linearGradient = array(0, 0, 0, 25);
    	$myChart->legend->backgroundColor->stops = array(array(0, 'rgb(217, 217, 217)'), array(1, 'rgb(255, 255, 255)'));
    	// labels to describe the content of axes
    	$myChart->xAxis->title->text = 'Week number';
    	$myChart->yAxis->title->text = '%';
    	
    	// tooltips
    	$myChart->tooltip->formatter = $this->Highcharts->createJsExpr(
    			"function() {
            return ''+ this.series.name +': '+ this.y +' ('+ Math.round(this.percentage) +'%)';}");
    
    	return $myChart;
    }
    
    public function commitChart(){
    	$myChart = $this->Highcharts->createChart();
    	$myChart->chart->renderTo = 'commitwrapper';
    	$myChart->chart->type = 'column';
    
    	$myChart->title = array(
        	'text' => 'Commits',
        	'y' => 20,
        	'align' => 'center',
        	'styleFont' => '18px Metrophobic, Arial, sans-serif',
        	'styleColor' => '#0099ff',
        );
		$myChart->subtitle->text = 'in total';
    	
    	// body of the chart
    	$myChart->chart->width =  800;
    	$myChart->chart->height = 500;

    	// $myChart->chart->alignTicks = FALSE;
    	$myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
    	$myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
    	// this chart doesn't need a legend
    	$myChart->legend->enabled = false;
		// tooltips
    	$myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return this.series.name +' produced <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
    	
    	// labels to describe the content of axes
    	$myChart->xAxis->title->text = 'Week number';
    	$myChart->yAxis->title->text = 'Total number of commits';
   
    	return $myChart;
    }
    public function testcaseChart() {
    	$myChart = $this->Highcharts->createChart();
    	$myChart->chart->renderTo = 'testcasewrapper';
    	$myChart->chart->type = 'area';
    
    	$myChart->title = array(
        	'text' => 'Test cases',
        	'y' => 20,
        	'align' => 'center',
        	'styleFont' => '18px Metrophobic, Arial, sans-serif',
        	'styleColor' => '#0099ff',
        );
    	
    	// body of the chart
    	$myChart->chart->width =  800;
    	$myChart->chart->height = 500;

    	// $myChart->chart->alignTicks = FALSE;
    	$myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
    	$myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
    	// legend below the charts
    	$myChart->legend->itemStyle = array('color' => '#222');
    	$myChart->legend->backgroundColor->linearGradient = array(0, 0, 0, 25);
    	$myChart->legend->backgroundColor->stops = array(array(0, 'rgb(217, 217, 217)'), array(1, 'rgb(255, 255, 255)'));
    	 
    	// tooltips etc
    	$myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return this.series.name +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
    	$myChart->plotOptions->area->marker->enabled = false;
    	// labels to describe the content of axes
    	$myChart->xAxis->title->text = 'Week number';
    	$myChart->yAxis->title->text = 'Total number of test cases';
    
    	return $myChart;
    }
    
    public function hoursChart(){
    	$myChart = $this->Highcharts->createChart();
    	$myChart->chart->renderTo = 'hourswrapper';
    	$myChart->chart->type = 'column';
    	$myChart->plotOptions->column->stacking = "normal";
    
    	$myChart->title = array(
        	'text' => 'Working hours',
        	'y' => 20,
        	'align' => 'center',
        	'styleFont' => '18px Metrophobic, Arial, sans-serif',
        	'styleColor' => '#0099ff',
        );
    	$myChart->subtitle->text = "categorized by type";
    
    	// body of the chart
    	$myChart->chart->width =  800;
    	$myChart->chart->height = 500;

    	// $myChart->chart->alignTicks = FALSE;
    	$myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
    	$myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
    	// this chart doesn't need a legend
    	$myChart->legend->enabled = false;
    	// labels of axes; unique x-axis
    	$myChart->xAxis->categories = array(
    			'Planning and management',
    			'Coding and testing',
    			'Studying',
    			'Documentation',
    			'Other'
    	);
    	$myChart->yAxis->title->text = 'Working hours';
		// tooltips etc
    	$myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return 'Total hours: ' +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Work type: '+ this.x;}");
    	$myChart->plotOptions->column->dataLabels->enabled = true;
    	$myChart->plotOptions->column->dataLabels->style->textShadow = false;
    	$myChart->plotOptions->column->dataLabels->style->color = '#444';
    	$myChart->plotOptions->column->dataLabels->style->fontSize = '1.2em';
    
    	return $myChart;
    }
    public function hoursPerWeekChart(){
    	$myChart = $this->Highcharts->createChart();
    	$myChart->chart->renderTo = 'hoursperweekwrapper';
    	$myChart->chart->type = 'line';

    
    	$myChart->title = array(
        	'text' => 'Working hours',
        	'y' => 20,
        	'align' => 'center',
        	'styleFont' => '18px Metrophobic, Arial, sans-serif',
        	'styleColor' => '#0099ff',
        );
    	$myChart->subtitle->text = "per week";
    
    	// body of the chart
    	$myChart->chart->width =  800;
    	$myChart->chart->height = 500;

    	// $myChart->chart->alignTicks = FALSE;
    	$myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
    	$myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
    	// this chart doesn't need a legend
    	$myChart->legend->enabled = false;
    	
        // labels of axes
    	
        $myChart->xAxis->title->text = 'Week number';
	$myChart->yAxis->title->text = 'Working hours';
    	
	// tooltips etc
    	$myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return 'Total hours: ' +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number: '+ this.x;}");
    	$myChart->plotOptions->area->marker->enabled = false;
    
    	return $myChart;
    }
    
    public function totalhourChart(){
		$myChart = $this->Highcharts->createChart();
		$myChart->chart->renderTo = 'totalhourwrapper';
		$myChart->chart->type = 'line';
	
		$myChart->title = array(
			'text' => 'Total hours',
			'y' => 20,
			'align' => 'center',
			'styleFont' => '18px Metrophobic, Arial, sans-serif',
			'styleColor' => '#0099ff',
		);
<<<<<<< HEAD
		$myChart->subtitle->text = 'cumulative (based on weeklyreports)';
=======
		$myChart->subtitle->text = 'cumulative';
>>>>>>> d9ad739... Fix error
		
		// body of the chart
		$myChart->chart->width =  800;
		$myChart->chart->height = 500;

		// $myChart->chart->alignTicks = FALSE;
		$myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
		$myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
		
		// this chart doesn't need a legend
		$myChart->legend->enabled = false;
		
		// labels to describe the content of axes
		$myChart->xAxis->title->text = 'Week number';
		$myChart->yAxis->title->text = 'Total amount of hours';
		
        $myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return 'Total hours at this point ' +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
        $myChart->plotOptions->area->marker->enabled = false;
        return $myChart;
    }
    
    
    public function risksProbChart() {
        $myChart = $this->Highcharts->createChart();
        $myChart->chart->renderTo = 'risksprobrapper';
        $myChart->chart->type = 'column';
        $myChart->title = array(
            'text' => 'Risks by Probability',
            'y' => 20,
            'align' => 'center',
            'styleFont' => '18px Metrophobic, Arial, sans-serif',
            'styleColor' => '#0099ff',
        );
        // body of the chart
        $myChart->chart->width =  800;
        $myChart->chart->height = 500;
      
        $myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
        $myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
        // legend below the charts
        $myChart->legend->itemStyle = array('color' => '#222');
        $myChart->legend->backgroundColor->linearGradient = array(0, 0, 0, 25);
        $myChart->legend->backgroundColor->stops = array(array(0, 'rgb(217, 217, 217)'), array(1, 'rgb(255, 255, 255)'));
        // labels to describe the content of axes
        $myChart->xAxis->title->text = 'Week number';
        $myChart->yAxis->title->text = 'Probability';
        
        // tooltips for the plotted graphs
        $myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return this.series.name +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
        $myChart->plotOptions->area->marker->enabled = false;
        
        return $myChart;
    }
    
    public function risksImpactChart() {
        $myChart = $this->Highcharts->createChart();
        $myChart->chart->renderTo = 'risksimpactwrapper';
        $myChart->chart->type = 'column';
        $myChart->title = array(
            'text' => 'Risks by Impact',
            'y' => 20,
            'align' => 'center',
            'styleFont' => '18px Metrophobic, Arial, sans-serif',
            'styleColor' => '#0099ff',
        );
        // body of the chart
        $myChart->chart->width =  800;
        $myChart->chart->height = 500;
      
        $myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
        $myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
        // legend below the charts
        $myChart->legend->itemStyle = array('color' => '#222');
        $myChart->legend->backgroundColor->linearGradient = array(0, 0, 0, 25);
        $myChart->legend->backgroundColor->stops = array(array(0, 'rgb(217, 217, 217)'), array(1, 'rgb(255, 255, 255)'));
        // labels to describe the content of axes
        $myChart->xAxis->title->text = 'Week number';
        $myChart->yAxis->title->text = 'Impact';
        
        // tooltips for the plotted graphs
        $myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return this.series.name +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
        $myChart->plotOptions->area->marker->enabled = false;
        
        return $myChart;
    }
    
    public function risksCombinedChart() {
        $myChart = $this->Highcharts->createChart();
        $myChart->chart->renderTo = 'riskscombinedwrapper';
        $myChart->chart->type = 'column';
        $myChart->title = array(
            'text' => 'Risks by Probability And Impact',
            'y' => 20,
            'align' => 'center',
            'styleFont' => '18px Metrophobic, Arial, sans-serif',
            'styleColor' => '#0099ff',
        );
        // body of the chart
        $myChart->chart->width =  800;
        $myChart->chart->height = 500;
      
        $myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
        $myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
        // legend below the charts
        $myChart->legend->itemStyle = array('color' => '#222');
        $myChart->legend->backgroundColor->linearGradient = array(0, 0, 0, 25);
        $myChart->legend->backgroundColor->stops = array(array(0, 'rgb(217, 217, 217)'), array(1, 'rgb(255, 255, 255)'));
        // labels to describe the content of axes
        $myChart->xAxis->title->text = 'Week number';
        $myChart->yAxis->title->text = 'Value';
        
        // tooltips for the plotted graphs
        $myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return this.series.name +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
        $myChart->plotOptions->area->marker->enabled = false;
        
        return $myChart;
    }
    
    

    public function derivedChart(){
        
   	$myChart = $this->Highcharts->createChart();
    	$myChart->chart->renderTo = 'derivedwrapper';
    	$myChart->chart->type = 'area';
    
    	$myChart->title = array(
        	'text' => 'Test cases',
        	'y' => 20,
        	'align' => 'center',
        	'styleFont' => '18px Metrophobic, Arial, sans-serif',
        	'styleColor' => '#0099ff',
        );
    	
    	// body of the chart
    	$myChart->chart->width =  800;
    	$myChart->chart->height = 500;

    	// $myChart->chart->alignTicks = FALSE;
    	$myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
    	$myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
    	// legend below the charts
    	$myChart->legend->itemStyle = array('color' => '#222');
    	$myChart->legend->backgroundColor->linearGradient = array(0, 0, 0, 25);
    	$myChart->legend->backgroundColor->stops = array(array(0, 'rgb(217, 217, 217)'), array(1, 'rgb(255, 255, 255)'));
    	 
    	// tooltips etc
    	$myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return this.series.name +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
    	$myChart->plotOptions->area->marker->enabled = false;
    	// labels to describe the content of axes
    	$myChart->xAxis->title->text = 'Week number';
    	$myChart->yAxis->title->text = 'Total number of test cases';
    
    	return $myChart;
    }    

    public function testiChart(){
		$myChart = $this->Highcharts->createChart();
		$myChart->chart->renderTo = 'testiwrapper';
		$myChart->chart->type = 'line';
	
		$myChart->title = array(
			'text' => 'Total hours of each project',
			'y' => 20,
			'align' => 'center',
			'styleFont' => '18px Metrophobic, Arial, sans-serif',
			'styleColor' => '#0099ff',
		);
		$myChart->subtitle->text = 'cumulative';
		
		// body of the chart
		$myChart->chart->width =  800;
		$myChart->chart->height = 500;

		// $myChart->chart->alignTicks = FALSE;
		$myChart->chart->backgroundColor->linearGradient = array(0, 0, 0, 300);
		$myChart->chart->backgroundColor->stops = array(array(0, 'rgb(217, 217, 255)'), array(1, 'rgb(255, 255, 255)'));
		
		// this chart doesn't need a legend
		$myChart->legend->enabled = false;
		
		// labels to describe the content of axes
		$myChart->xAxis->title->text = 'Week number';
		$myChart->yAxis->title->text = 'Total amount of hours';
		
        $myChart->tooltip->formatter = $this->Highcharts->createJsExpr("function() {
        return 'Total hours at this point ' +' <b>'+
        Highcharts.numberFormat(this.y, 0) +'</b><br/>Week number '+ this.x;}");
        $myChart->plotOptions->area->marker->enabled = false;
        return $myChart;
    }
    

    public function isAuthorized($user)
    {      
        return True;
    }
}
