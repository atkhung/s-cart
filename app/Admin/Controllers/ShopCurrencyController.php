<?php
#app/Http/Admin/Controllers/ShopCurrencyController.php
namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ShopCurrency;
use Illuminate\Http\Request;
use Validator;

class ShopCurrencyController extends Controller
{

    public function index()
    {

        $data = [
            'title' => trans('currency.admin.list'),
            'subTitle' => '',
            'icon' => 'fa fa-indent',
            'menuRight' => [],
            'menuLeft' => [],
            'topMenuRight' => [],
            'topMenuLeft' => [],
            'urlDeleteItem' => route('admin_currency.delete'),
            'removeList' => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 0, // 1 - Enable button refresh
            'buttonSort' => 0, // 1 - Enable button sort
            'css' => '', 
            'js' => '',
        ];

        $listTh = [
            'name' => trans('currency.name'),
            'code' => trans('currency.code'),
            'symbol' => trans('currency.symbol'),
            'exchange_rate' => trans('currency.exchange_rate'),
            'precision' => trans('currency.precision'),
            'symbol_first' => trans('currency.symbol_first'),
            'thousands' => trans('currency.thousands'),
            'sort' => trans('currency.sort'),
            'status' => trans('currency.status'),
            'action' => trans('currency.admin.action'),
        ];

        $sort_order = request('sort_order') ?? 'id_desc';
        $keyword = request('keyword') ?? '';
        $arrSort = [
            'id__desc' => trans('currency.admin.sort_order.id_desc'),
            'id__asc' => trans('currency.admin.sort_order.id_asc'),
            'name__desc' => trans('currency.admin.sort_order.name_desc'),
            'name__asc' => trans('currency.admin.sort_order.name_asc'),
        ];
        $obj = new ShopCurrency;
        if ($keyword) {
            $obj = $obj->whereRaw('(code = "' . $keyword . '" OR name like "%' . $keyword . '%" )');
        }

        if ($sort_order && array_key_exists($sort_order, $arrSort)) {
            $field = explode('__', $sort_order)[0];
            $sort_field = explode('__', $sort_order)[1];
            $obj = $obj->orderBy($field, $sort_field);

        } else {
            $obj = $obj->orderBy('id', 'desc');
        }
        $dataTmp = $obj->paginate(20);

        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $dataTr[] = [
                'name' => $row['name'],
                'code' => $row['code'],
                'symbol' => $row['symbol'],
                'exchange_rate' => $row['exchange_rate'],
                'precision' => $row['precision'],
                'symbol_first' => $row['symbol_first'],
                'thousands' => $row['thousands'],
                'sort' => $row['sort'],
                'status' => $row['status'] ? '<span class="label label-success">ON</span>' : '<span class="label label-danger">OFF</span>',
                'action' => '
                    <a href="' . route('admin_currency.edit', ['id' => $row['id']]) . '"><span title="' . trans('currency.admin.edit') . '" type="button" class="btn btn-flat btn-primary"><i class="fa fa-edit"></i></span></a>&nbsp;

                  <span ' . (in_array($row['id'], SC_GUARD_CURRENCY) ? "style='display:none'" : "") . ' onclick="deleteItem(' . $row['id'] . ');"  title="' . trans('currency.admin.delete') . '" class="btn btn-flat btn-danger"><i class="fa fa-trash"></i></span>
                  ',
            ];
        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links('admin.component.pagination');
        $data['resultItems'] = trans('currency.admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'item_total' => $dataTmp->total()]);

//menuRight
        $data['menuRight'][] = '<a href="' . route('admin_currency.create') . '" class="btn  btn-success  btn-flat" title="New" id="button_create_new">
        <i class="fa fa-plus" title="'.trans('admin.add_new').'"></i>
                           </a>';
//=menuRight

//menuSort        
        $optionSort = '';
        foreach ($arrSort as $key => $status) {
            $optionSort .= '<option  ' . (($sort_order == $key) ? "selected" : "") . ' value="' . $key . '">' . $status . '</option>';
        }

        $data['urlSort'] = route('admin_currency.index');
        $data['optionSort'] = $optionSort;
//=menuSort

//menuSearch
        $data['topMenuRight'][] = '
                <form action="' . route('admin_currency.index') . '" id="button_search">
                   <div onclick="$(this).submit();" class="btn-group pull-right">
                           <a class="btn btn-flat btn-primary" title="Refresh">
                              <i class="fa  fa-search"></i>
                           </a>
                   </div>
                   <div class="btn-group pull-right">
                         <div class="form-group">
                           <input type="text" name="keyword" class="form-control" placeholder="' . trans('currency.admin.search_place') . '" value="' . $keyword . '">
                         </div>
                   </div>
                </form>';
//=menuSearch

        return view('admin.screen.list')
            ->with($data);
    }

/**
 * Form create new order in admin
 * @return [type] [description]
 */
    public function create()
    {
        $data = [
            'title' => trans('currency.admin.add_new_title'),
            'subTitle' => '',
            'title_description' => trans('currency.admin.add_new_des'),
            'icon' => 'fa fa-plus',
            'currency' => [],
            'url_action' => route('admin_currency.create'),
        ];
        return view('admin.screen.currency')
            ->with($data);
    }

/**
 * Post create new order in admin
 * @return [type] [description]
 */
    public function postCreate()
    {
        $data = request()->all();
        $dataOrigin = request()->all();
        $validator = Validator::make($data, [
            'symbol' => 'required',
            'exchange_rate' => 'required|numeric|gt:0',
            'precision' => 'required',
            'symbol_first' => 'required',
            'thousands' => 'required',
            'sort' => 'numeric|min:0',
            'name' => 'required|string|max:100',
            'code' => 'required|unique:'.SC_DB_PREFIX.'shop_currency,code',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $dataInsert = [
            'name' => $data['name'],
            'code' => $data['code'],
            'symbol' => $data['symbol'],
            'exchange_rate' => $data['exchange_rate'],
            'precision' => $data['precision'],
            'symbol_first' => $data['symbol_first'],
            'thousands' => $data['thousands'],
            'status' => empty($data['status']) ? 0 : 1,
            'sort' => (int) $data['sort'],
        ];
        ShopCurrency::create($dataInsert);

        return redirect()->route('admin_currency.index')->with('success', trans('currency.admin.create_success'));

    }

/**
 * Form edit
 */
    public function edit($id)
    {
        $currency = ShopCurrency::find($id);
        if ($currency === null) {
            return 'no data';
        }
        $data = [
            'title' => trans('currency.admin.edit'),
            'subTitle' => '',
            'title_description' => '',
            'icon' => 'fa fa-pencil-square-o',
            'currency' => $currency,
            'url_action' => route('admin_currency.edit', ['id' => $currency['id']]),
        ];
        return view('admin.screen.currency')
            ->with($data);
    }

/**
 * update status
 */
    public function postEdit($id)
    {
        $currency = ShopCurrency::find($id);
        $data = request()->all();
        $dataOrigin = request()->all();
        $validator = Validator::make($data, [
            'symbol' => 'required',
            'exchange_rate' => 'required|numeric|gt:0',
            'precision' => 'required',
            'symbol_first' => 'required',
            'thousands' => 'required',
            'sort' => 'numeric|min:0',
            'name' => 'required|string|max:100',
            'code' => 'required|unique:'.SC_DB_PREFIX.'shop_currency,code,' . $currency->id . ',id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
//Edit

        $dataUpdate = [
            'name' => $data['name'],
            'code' => $data['code'],
            'symbol' => $data['symbol'],
            'exchange_rate' => $data['exchange_rate'],
            'precision' => $data['precision'],
            'symbol_first' => $data['symbol_first'],
            'thousands' => $data['thousands'],
            'sort' => (int) $data['sort'],

        ];

        //Check status before change
        $check = ShopCurrency::where('status', 1)->where('code', '<>', $data['code'])->count();
        if ($check) {
            $dataUpdate['status'] = empty($data['status']) ? 0 : 1;
        } else {
            $dataUpdate['status'] = 1;
        }
        //End check status

        $obj = ShopCurrency::find($id);
        $obj->update($dataUpdate);

//
        return redirect()->route('admin_currency.index')->with('success', trans('currency.admin.edit_success'));

    }

/*
Delete list item
Need mothod destroy to boot deleting in model
 */
    public function deleteList()
    {
        if (!request()->ajax()) {
            return response()->json(['error' => 1, 'msg' => 'Method not allow!']);
        } else {
            $ids = request('ids');
            $arrID = explode(',', $ids);
            $arrID = array_diff($arrID, SC_GUARD_CURRENCY);
            ShopCurrency::destroy($arrID);
            return response()->json(['error' => 0, 'msg' => '']);
        }
    }

}
