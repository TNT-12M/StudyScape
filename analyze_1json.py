import json
with open(r'c:\Users\Administrator\Desktop\StudyScape\1.json','r',encoding='utf-8') as f:
    data = json.load(f)
print('total questions:', len(data))
types = {}
imgs = 0
opts = 0
for q in data:
    t = q.get('question_type','')
    types[t] = types.get(t,0)+1
    if q.get('question_images') or q.get('analysis_images'):
        imgs += 1
    if q.get('question_tables'):
        imgs += 1  # tables also need html
    if q.get('question_options'):
        opts += 1
print('question_type distribution:', types)
print('items with images/tables:', imgs)
print('items with options:', opts)
print('first content length (chars):', len(data[0]['question_content']))
print('9th content length:', len(data[8]['question_content']))
print('9th has images:', len(data[8].get('question_images',[])))
print('9th images[0] prefix:', str(data[8].get('question_images',[''])[0])[:40] if data[8].get('question_images') else 'none')
