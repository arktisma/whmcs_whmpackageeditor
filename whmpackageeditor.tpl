<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.2.15/angular.min.js"></script>
<script type="text/javascript">
(function($) {

var app = angular.module("whmpackageeditor", []);

app.factory("api", ["$q", "$http", function($q, $http) {
	return {
		_post: function(path, data) {
			var d = $q.defer();
			$http.post(path, data).success(function(data) {
			  d.resolve(data);
			}).error(function(data) {
			  d.reject(data);
			});
			return d.promise;
		},
		_get: function(path, cache) {
			var d = $q.defer();
			$http.get(path, { cache: cache }).success(function(data) {
			  d.resolve(data);
			}).error(function(data) {
			  d.reject(data);
			});
			return d.promise;
		},
		GetPlans: function() {
			return this._get("/modules/addons/whmpackageeditor/plans");
		},
		Send: function(plan) {
			return this._post("/modules/addons/whmpackageeditor/update", {
				plan: plan
			});
		}
	};
}]);

app.factory("normalize", function() {
	return function(orig) {
		if(typeof(orig) !== "string" || orig.length === 0) {
			return orig;
		}
		var out = orig.replace(/\s+/g, "");
		if(out[0] === "+") {
			out = out.slice(1);
		}
		if(out.indexOf("04") === 0) {
			out = "+61" + out.slice(1);
		} else {
			return out;
		}
		return out;
	};
});

app.controller("MainCtrl", ["$scope", "api", "normalize", function($scope, api, normalize) {
	$scope.sending = false;
	$scope.plans = [];
	$scope.reset = function() {
		$scope.plan = null;
		$scope.selected = null;
	}
	$scope.update = function() {
		$scope.plan = angular.copy($scope.selected);
	};
	$scope.maxLength = 160;
	$scope.submit = function() {
		$scope.error = null;
		$scope.sending = true;
		$scope.results = null;
		api.Send($scope.plan).then(function(result) {
			$scope.sending = false;
			$scope.results = result;
			api.GetPlans().then(function(data) {
				$scope.plans = data;
			}, function(err) {
				window.location.reload();
			});
		}, function(err) {
			$scope.sending = false;
			if(typeof(err.error) !== "undefined") {
				$scope.error = err.error;
			} else {
				$scope.error = err;
			}
			$scope.reset();
		});
	};
	$scope.reset();
	api.GetPlans().then(function(data) {
		$scope.plans = data;
	}, function(err) {
		$scope.error = err;
	});	
}]);

})(jQuery);
</script>
<div ng-app="whmpackageeditor" ng-controller="MainCtrl">

<h2>Modify WHMCS/WHM plan limits together</h2>
<p>Please use <a href="https://serversaurus.com.au/sync_packages.php" target="_blank">this page</a> to check if packages are in sync between WHM/WHMCS.</p>
<hr>

<form ng-submit="submit()">
	<fieldset>
		<label>Choose a plan:</label>
		<select name="client" ng-change="update()" ng-model="selected" ng-options="(plan.label + ' - ' + plan.name) for plan in plans | orderBy:'label'"></select>
		<div ng-show="plan!= null">
			<p>Editing package <strong>{{plan.label}} ({{plan.name}})</strong></p>
			<textarea name="description" ng-model="plan.description" style="width: 100%; margin: 10px 0; min-height: 100px;"></textarea>
			<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
				<tbody>
					<tr>
						<td class="fieldlabel">Overages enabled</td>
						<td class="fieldarea"><input type="checkbox" ng-checked="plan.limits.overage" disabled="disabled"/></td>
					</tr>
					<tr>
						<td class="fieldlabel">Disk Limit (MB)</td>
						<td class="fieldarea"><input type="number" ng-model="plan.limits.disk"/></td>
						<td class="fieldlabel">Bandwidth Limit (MB)</td>
						<td class="fieldarea"><input type="number" ng-model="plan.limits.bandwidth"/></td>
					</tr>
				</body>
			</table>
			<p><input ng-show="sending === false" type="submit" value="Save!"><span ng-show="sending === true">Updating ...</span></p>
			<ul style="color: darkgreen; padding: 0 5px;" ng-show="results != null && results.length > 0">
			<li ng-repeat="r in results">{{r}}</li>
			</ul>
		</div>
	</fieldset>
</form>
<div ng-show="error != null">Error occured: <strong style="color: red;">{{error}}</strong></div>
</div>
