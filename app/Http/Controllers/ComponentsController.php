<?php
namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Company;
use App\Models\Component;
use App\Models\CustomField;
use App\Models\Setting;
use App\Models\User;
use App\Models\Asset;
use Auth;
use Config;
use DB;
use Input;
use Lang;
use Mail;
use Redirect;
use Slack;
use Str;
use View;
use Validator;
use Illuminate\Http\Request;
use Gate;

/**
 * This class controls all actions related to Components for
 * the Snipe-IT Asset Management application.
 *
 * @version    v1.0
 */
class ComponentsController extends Controller
{
    /**
    * Returns a view that invokes the ajax tables which actually contains
    * the content for the components listing, which is generated in getDatatable.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see ComponentsController::getDatatable() method that generates the JSON response
    * @since [v3.0]
     * @return \Illuminate\Contracts\View\View
    */
    public function index()
    {
        $this->authorize('view', Component::class);
        return view('components/index');
    }


    /**
    * Returns a form to create a new component.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see ComponentsController::postCreate() method that stores the data
    * @since [v3.0]
    * @return \Illuminate\Contracts\View\View
    */
    public function create()
    {
        $this->authorize('create', Component::class);
        // Show the page
        return view('components/edit')
            ->with('item', new Component)
            ->with('category_list', Helper::categoryList('component'))
            ->with('company_list', Helper::companyList())
            ->with('location_list', Helper::locationsList());
    }


    /**
    * Validate and store data for new component.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see ComponentsController::getCreate() method that generates the view
    * @since [v3.0]
    * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $this->authorize('create', Component::class);
        $component = new Component();
        $component->name                   = Input::get('name');
        $component->category_id            = Input::get('category_id');
        $component->location_id            = Input::get('location_id');
        $component->company_id             = Company::getIdForCurrentUser(Input::get('company_id'));
        $component->order_number           = Input::get('order_number');
        $component->min_amt                = Input::get('min_amt');
        $component->serial                 = Input::get('serial');
        $component->purchase_date       = Input::get('purchase_date');
        $component->purchase_cost       = request('purchase_cost');
        $component->qty                    = Input::get('qty');
        $component->user_id                = Auth::id();

        if ($component->save()) {
            return redirect()->route('components.index')->with('success', trans('admin/components/message.create.success'));
        }
        return redirect()->back()->withInput()->withErrors($component->getErrors());
    }

    /**
    * Return a view to edit a component.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see ComponentsController::postEdit() method that stores the data.
    * @since [v3.0]
    * @param int $componentId
    * @return \Illuminate\Contracts\View\View
     */
    public function edit($componentId = null)
    {
        if (is_null($item = Component::find($componentId))) {
            return redirect()->route('components.index')->with('error', trans('admin/components/message.does_not_exist'));
        }

        $this->authorize('update', $item);

        return view('components/edit', compact('item'))
            ->with('category_list', Helper::categoryList('component'))
            ->with('company_list', Helper::companyList())
            ->with('location_list', Helper::locationsList());
    }


    /**
    * Return a view to edit a component.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see ComponentsController::getEdit() method presents the form.
    * @param int $componentId
    * @since [v3.0]
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update($componentId = null)
    {
        if (is_null($component = Component::find($componentId))) {
            return redirect()->route('components.index')->with('error', trans('admin/components/message.does_not_exist'));
        }

        $this->authorize('update', $component);


        // Update the component data
        $component->name                   = Input::get('name');
        $component->category_id            = Input::get('category_id');
        $component->location_id            = Input::get('location_id');
        $component->company_id             = Company::getIdForCurrentUser(Input::get('company_id'));
        $component->order_number           = Input::get('order_number');
        $component->min_amt                = Input::get('min_amt');
        $component->serial                 = Input::get('serial');
        $component->purchase_date          = Input::get('purchase_date');
        $component->purchase_cost          = request('purchase_cost');
        $component->qty                    = Input::get('qty');

        if ($component->save()) {
            return redirect()->route('components.index')->with('success', trans('admin/components/message.update.success'));
        }
        return redirect()->back()->withInput()->withErrors($component->getErrors());
    }

    /**
    * Delete a component.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @since [v3.0]
    * @param int $componentId
    * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($componentId)
    {
        if (is_null($component = Component::find($componentId))) {
            return redirect()->route('components.index')->with('error', trans('admin/components/message.not_found'));
        }

        $this->authorize('delete', $component);
        $component->delete();
        return redirect()->route('components.index')->with('success', trans('admin/components/message.delete.success'));
    }

    public function postBulk($componentId = null)
    {
        //$this->authorize('checkout', $component)
        echo 'Stubbed - not yet complete';
    }

    public function postBulkSave($componentId = null)
    {
        //$this->authorize('edit', Component::class);
        echo 'Stubbed - not yet complete';
    }


    /**
    * Return a view to display component information.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see ComponentsController::getDataView() method that generates the JSON response
    * @since [v3.0]
    * @param int $componentId
    * @return \Illuminate\Contracts\View\View
     */
    public function show($componentId = null)
    {
        $component = Component::find($componentId);

        if (isset($component->id)) {
            $this->authorize('view', $component);
            return view('components/view', compact('component'));
        }
        // Prepare the error message
        $error = trans('admin/components/message.does_not_exist', compact('id'));
        // Redirect to the user management page
        return redirect()->route('components.index')->with('error', $error);
    }

    /**
    * Returns a view that allows the checkout of a component to an asset.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see ComponentsController::postCheckout() method that stores the data.
    * @since [v3.0]
    * @param int $componentId
    * @return \Illuminate\Contracts\View\View
     */
    public function getCheckout($componentId)
    {
        // Check if the component exists
        if (is_null($component = Component::find($componentId))) {
            // Redirect to the component management page with error
            return redirect()->route('components.index')->with('error', trans('admin/components/message.not_found'));
        }
        $this->authorize('checkout', $component);
        return view('components/checkout', compact('component'))->with('assets_list', Helper::detailedAssetList());
    }

    /**
     * Validate and store checkout data.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @see ComponentsController::getCheckout() method that returns the form.
     * @since [v3.0]
     * @param Request $request
     * @param int $componentId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postCheckout(Request $request, $componentId)
    {
        // Check if the component exists
        if (is_null($component = Component::find($componentId))) {
            // Redirect to the component management page with error
            return redirect()->route('components.index')->with('error', trans('admin/components/message.not_found'));
        }

        $this->authorize('checkout', $component);

        $max_to_checkout = $component->numRemaining();
        $validator = Validator::make($request->all(), [
            "asset_id"          => "required",
            "assigned_qty"      => "required|numeric|between:1,$max_to_checkout"
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $admin_user = Auth::user();
        $asset_id = e(Input::get('asset_id'));

      // Check if the user exists
        if (is_null($asset = Asset::find($asset_id))) {
            // Redirect to the component management page with error
            return redirect()->route('components.index')->with('error', trans('admin/components/message.asset_does_not_exist'));
        }

      // Update the component data
        $component->asset_id =   $asset_id;

        $component->assets()->attach($component->id, [
            'component_id' => $component->id,
            'user_id' => $admin_user->id,
            'created_at' => date('Y-m-d H:i:s'),
            'assigned_qty' => Input::get('assigned_qty'),
            'asset_id' => $asset_id
        ]);

        $component->logCheckout(e(Input::get('note')), $asset);
        return redirect()->route('components.index')->with('success', trans('admin/components/message.checkout.success'));
    }


    /**
    * Return JSON data to populate the components view,
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see ComponentsController::getView() method that returns the view.
    * @since [v3.0]
    * @param int $componentId
    * @return string JSON
    */
    public function getDataView($componentId)
    {
        if (is_null($component = Component::with('assets')->find($componentId))) {
            // Redirect to the component management page with error
            return redirect()->route('components.index')->with('error', trans('admin/components/message.not_found'));
        }

        if (!Company::isCurrentUserHasAccess($component)) {
            return ['total' => 0, 'rows' => []];
        }
        $this->authorize('view', $component);

        $rows = array();
        $all_custom_fields = CustomField::all(); // Cached for table;
        foreach ($component->assets as $component_assignment) {
            $rows[] = $component_assignment->present()->forDataTable($all_custom_fields);
        }

        $componentCount = $component->assets->count();
        $data = array('total' => $componentCount, 'rows' => $rows);
        return $data;
    }
}
